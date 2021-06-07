<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Aws\S3\S3Client;
use Hammerstone\Sidecar\Exceptions\SidecarException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipStream\Option\Archive;
use ZipStream\Option\File as FileOptions;
use ZipStream\ZipStream;

class Package
{
    /**
     * @var array
     */
    protected $include = [];

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
     * @param array $paths
     * @return static
     */
    public static function make($paths = [])
    {
        return new static($paths);
    }

    /**
     * @param array $paths
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
     * @param $paths
     * @return $this
     */
    public function exclude($paths)
    {
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
     * @return string
     */
    public function hash()
    {
        return $this->files()->reduce(function ($carry, $file) {
            return md5($carry . md5_file($file));
        });
    }

    /**
     * @return string[]
     * @throws SidecarException
     */
    public function deploymentConfiguration()
    {
        try {
            $key = $this->upload();
        } catch (\ZipStream\Exception $e) {
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
     * @throws \ZipStream\Exception
     */
    public function upload()
    {
        Sidecar::log('Packaging function code.');

        $this->registerStreamWrapper();

        $filename = $this->getFilename();

        $path = "s3://{$this->bucket()}/$filename";

        // If it already exists we can bail early.
        if (file_exists($path)) {
            Sidecar::log("Package unchanged, reusing previous code package at $path.");

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

        foreach ($this->files() as $file) {
            $file = $this->prependBasePath($file);

            $options = tap(new FileOptions)->setTime(Carbon::now());

            // Remove the base path so that everything inside the zip is
            // relative to the project root. Add the base path so that
            // ZipStream can find it read off the disk.
            $zip->addFileFromPath(
                $this->removeBasePath($file),
                $file,
                $options
            );
        }

        $zip->finish();

        fclose($stream);

        Sidecar::log('Zip file created at ' . $path);

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

        return $this->getBasePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
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
     * @return string
     */
    protected function registerStreamWrapper()
    {
        // Register the s3:// stream wrapper.
        // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-stream-wrapper.html
        $client = new S3Client([
            'version' => 'latest',
            'region' => config('sidecar.aws_region'),
            'credentials' => [
                'key' => config('sidecar.aws_key'),
                'secret' => config('sidecar.aws_secret'),
            ]
        ]);

        $client->registerStreamWrapper();
    }
}
