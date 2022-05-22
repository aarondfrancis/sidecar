<?php

/**
 * @author Spatie bvba info@spatie.be
 * @license MIT
 *
 * @see https://github.com/spatie/laravel-backup/blob/master/src/Tasks/Backup/FileSelection.php
 */

namespace Hammerstone\Sidecar;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder as SymfonyFinder;

class Finder
{
    /** @var \Illuminate\Support\Collection */
    protected $includeFilesAndDirectories;

    /** @var \Illuminate\Support\Collection */
    protected $excludeFilesAndDirectories;

    /** @var bool */
    protected $shouldFollowLinks = false;

    /**
     * @param  array  $include
     * @param  array  $exclude
     * @return Finder
     */
    public static function create($include = [], $exclude = [])
    {
        return new static($include, $exclude);
    }

    /**
     * @param  array  $include
     * @param  array  $exclude
     */
    public function __construct($include = [], $exclude = [])
    {
        $this->includeFilesAndDirectories = collect($include);
        $this->excludeFilesAndDirectories = collect($exclude);
    }

    /**
     * Do not included the given files and directories.
     *
     * @param  array|string  $excludeFilesAndDirectories
     * @return Finder
     */
    public function excludeFilesFrom($excludeFilesAndDirectories)
    {
        $this->excludeFilesAndDirectories = $this->excludeFilesAndDirectories->merge($this->sanitize($excludeFilesAndDirectories));

        return $this;
    }

    public function shouldFollowLinks(bool $shouldFollowLinks)
    {
        $this->shouldFollowLinks = $shouldFollowLinks;

        return $this;
    }

    public function selected()
    {
        return collect($this->yieldSelectedFiles())->diff($this->excludeFilesAndDirectories);
    }

    /**
     * @return \Generator|string[]
     */
    protected function yieldSelectedFiles()
    {
        if ($this->includeFilesAndDirectories->isEmpty()) {
            return [];
        }

        $finder = (new SymfonyFinder)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->files();

        if ($this->shouldFollowLinks) {
            $finder->followLinks();
        }

        foreach ($this->includedFiles() as $includedFile) {
            yield $includedFile;
        }

        if (!count($this->includedDirectories())) {
            return;
        }

        $finder->in($this->includedDirectories());

        foreach ($finder->getIterator() as $file) {
            if ($this->shouldExclude($file)) {
                continue;
            }

            yield $file->getPathname();
        }
    }

    protected function includedFiles()
    {
        return $this->includeFilesAndDirectories
            ->each(function ($path) {
                if (!is_file($path) && !is_dir($path)) {
                    throw new \Exception($path . ' is neither a file nor a directory.');
                }
            })
            ->filter(function ($path) {
                return is_file($path);
            })
            ->toArray();
    }

    protected function includedDirectories()
    {
        return $this->includeFilesAndDirectories
            ->each(function ($path) {
                if (!is_file($path) && !is_dir($path)) {
                    throw new \Exception($path . ' is neither a file nor a directory.');
                }
            })
            ->reject(function ($path) {
                return is_file($path);
            })
            ->toArray();
    }

    protected function shouldExclude(string $path): bool
    {
        foreach ($this->excludeFilesAndDirectories as $excludedPath) {
            if (Str::startsWith(realpath($path), $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string|array  $paths
     * @return \Illuminate\Support\Collection
     */
    protected function sanitize($paths)
    {
        return collect($paths)
            ->reject(function ($path) {
                return $path === '';
            })
            ->flatMap(function ($path) {
                return glob($path);
            })
            ->map(function ($path) {
                return realpath($path);
            })
            ->reject(function ($path) {
                return $path === false;
            });
    }
}
