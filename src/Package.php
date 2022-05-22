<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Hammerstone\Sidecar\Clients\S3Client;
use Hammerstone\Sidecar\Exceptions\SidecarException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use ZipStream\Exception;
use ZipStream\Option\Archive;
use ZipStream\Option\File as FileOptions;
use ZipStream\ZipStream;

class Package
{
    use Macroable;

    /**
     * If your package is a container image, it does not require
     * a handler function. Use this constant instead.
     *
     * @see https://hammerstone.dev/sidecar/docs/main/functions/handlers-and-packages
     *
     * @var string
     */
    public const CONTAINER_HANDLER = 'container';

    /**
     * @var array
     */
    protected $include = [];

    /**
     * @var array
     */
    protected $exactIncludes = [];

    /**
     * @var array
     */
    protected $stringContents = [];

    /**
     * @var array
     */
    protected $exclude = [];

    /**
     * @var Collection|null
     */
    protected $files;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @param  array  $paths
     * @return static
     */
    public static function make($paths = [])
    {
        return new static($paths);
    }

    /**
     * @param  array  $paths
     */
    public function __construct($paths = [])
    {
        $include = [];
        $exclude = [];

        foreach ($paths as $path) {
            if (Str::startsWith($path, '!')) {
                $exclude[] = Str::replaceFirst('!', '', $path);
            } else {
                $include[] = $path;
            }
        }

        $this->include($include)->exclude($exclude);
    }

    /**
     * @param $paths
     * @return $this
     */
    public function include($paths)
    {
        $this->include = array_merge($this->include, $this->pathsForMerging($paths));

        $this->files = null;

        return $this;
    }

    /**
     * Include files with explicit control over the source and
     * destination. The keys should be the source, while the
     * values should be the destination, i.e. the path
     * within the zip file.
     *
     * @param  array  $files
     * @return $this
     */
    public function includeExactly($files, $followLinks = false)
    {
        $expanded = [];

        foreach ($files as $source => $destination) {
            // Files can stay as is, they just get added directly.
            if (!is_dir($source)) {
                continue;
            }

            $finder = Finder::create($source)->shouldFollowLinks($followLinks)->selected();

            foreach ($finder as $file) {
                // For directories, we need to replace the source
                // directory with the destination directory.
                $expanded[$file] = $destination . Str::after($file, $source);
            }

            // Now that all the files are included in the expanded array,
            // we don't need the directory in the files array.
            Arr::pull($files, $source);
        }

        $files = array_merge(
            $this->exactIncludes, $files, $expanded
        );

        ksort($files);

        $this->exactIncludes = $files;

        $this->files = null;

        return $this;
    }

    /**
     * Include a string as a file. The path is the
     * destination within the zip file.
     *
     * @param $path
     * @param $contents
     * @return $this
     */
    public function includeString($path, $contents)
    {
        return $this->includeStrings([
            $path => $contents
        ]);
    }

    /**
     * Include strings as files. The keys are paths within the
     * zip file and the values are the contents of the files.
     *
     * @param  array  $strings
     * @return $this
     */
    public function includeStrings($strings)
    {
        $this->stringContents = array_merge($this->stringContents, $strings);

        $this->files = null;

        return $this;
    }

    /**
     * @param $paths
     * @return $this
     */
    public function exclude($paths)
    {
        // If someone passed e.g. "!ignore.js", we'll just silently
        // strip it off here. The array style happily accepts the
        // exclamation as a negation flag, and I can see that
        // causing unnecessary DX issues.
        $paths = array_map(function ($path) {
            if (Str::startsWith($path, '!')) {
                $path = Str::replaceFirst('!', '', $path);
            }

            return $path;
        }, Arr::wrap($paths));

        $this->exclude = array_merge($this->exclude, $this->pathsForMerging($paths));

        $this->files = null;

        return $this;
    }

    /**
     * @return array
     */
    public function getIncludedPaths()
    {
        return $this->include;
    }

    /**
     * @return array
     */
    public function getExcludedPaths()
    {
        return $this->exclude;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;

        return $this;
    }

    /**
     * @return Collection
     */
    public function files()
    {
        if ($this->files) {
            return $this->files;
        }

        $selected = Finder::create($this->include, $this->exclude)->selected();

        return $this->files = collect($selected)
            ->diff($this->exclude)
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * A hash of the contents.
     *
     * @return string
     */
    public function hash()
    {
        $hash = $this->files()->reduce(function ($carry, $file) {
            return md5($carry . $this->removeBasePath($file) . md5_file($file));
        });

        // We cannot use the collection `reduce` method here, because it
        // doesn't provide the key in some versions of Laravel.
        // @see https://github.com/hammerstonedev/sidecar/runs/4710405742
        foreach ($this->exactIncludes as $source => $destination) {
            $hash = md5($hash . $destination . md5_file($source));
        }

        foreach ($this->stringContents as $destination => $stringContent) {
            $hash = md5($hash . $destination . md5($stringContent));
        }

        return $hash;
    }

    /**
     * @return string[]
     *
     * @throws SidecarException
     */
    public function deploymentConfiguration()
    {
        try {
            $key = $this->upload();
        } catch (Exception $e) {
            throw new SidecarException('Sidecar could not create ZIP: ' . $e->getMessage());
        }

        return [
            'S3Bucket' => $this->bucket(),
            'S3Key' => $key,
        ];
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        // Base the filename on the contents, that way we can skip
        // uploading the same thing over and over. Include the
        // version so that if we ever change how we zip, we
        // won't be reusing old stuff.
        return "sidecar/{$this->packagingVersion()}-{$this->hash()}.zip";
    }

    /**
     * @return string
     */
    public function normalizeSeparators($file)
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $file);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function upload()
    {
        Sidecar::log('Packaging files for deployment.');

        $this->registerStreamWrapper();

        $filename = $this->getFilename();

        $path = "s3://{$this->bucket()}/$filename";

        // If it already exists we can bail early.
        if (file_exists($path)) {
            Sidecar::log("Package unchanged. Reusing $path.");

            return $filename;
        }

        Sidecar::log('Creating a new zip file.');

        // Stream the zip directly to S3, without it ever touching
        // a disk anywhere. Important because we might not have
        // a writeable local disk!
        $stream = fopen($path, 'w');

        $options = new Archive;
        $options->setEnableZip64(false);
        $options->setOutputStream($stream);

        $zip = new ZipStream($name = null, $options);

        // Set the time to now so that hashes are
        // stable during testing.
        $options = tap(new FileOptions)->setTime(Carbon::now());

        foreach ($this->files() as $file) {
            // Add the base path so that ZipStream can
            // find it read off the disk.
            $file = $this->prependBasePath($file);

            // Remove the base path so that everything inside
            // the zip is relative to the project root.
            $zip->addFileFromPath(
                $this->normalizeSeparators($this->removeBasePath($file)), $file, $options
            );
        }

        foreach ($this->exactIncludes as $source => $destination) {
            $zip->addFileFromPath(
                $this->normalizeSeparators($destination), $source, $options
            );
        }

        foreach ($this->stringContents as $destination => $stringContent) {
            $zip->addFile(
                $this->normalizeSeparators($destination), $stringContent, $options
            );
        }

        $zip->finish();

        $size = fstat($stream)['size'] / 1024 / 1024;
        $size = round($size, 2);

        fclose($stream);

        Sidecar::log("Zip file created at $path. ({$size}MB)");

        return $filename;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath ?? config('sidecar.package_base_path') ?? base_path();
    }

    /**
     * @param $paths
     * @return array
     */
    protected function pathsForMerging($paths)
    {
        return array_map(function ($path) {
            // `*` means everything in the base directory, whatever
            // that may be. By resetting it to an empty string and
            // prepending the base path, everything is included.
            if ($path === '*') {
                $path = '';
            }

            // Make every path relative to the base directory.
            return $this->prependBasePath($path);
        }, Arr::wrap($paths));
    }

    /**
     * @param $path
     * @return string
     */
    protected function prependBasePath($path)
    {
        $path = $this->removeBasePath($path);

        return $this->getBasePath() . ($path ? '/' . $path : $path);
    }

    /**
     * @param $path
     * @return string
     */
    protected function removeBasePath($path)
    {
        return preg_replace('/^' . preg_quote($this->getBasePath(), '/') . '/i', '', $path);
    }

    /**
     * @return string
     */
    protected function packagingVersion()
    {
        return '001';
    }

    /**
     * @return string
     */
    protected function bucket()
    {
        return config('sidecar.aws_bucket');
    }

    /**
     * @return void
     */
    protected function registerStreamWrapper()
    {
        // Register the s3:// stream wrapper.
        // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-stream-wrapper.html
        app(S3Client::class)->registerStreamWrapper();
    }
}
