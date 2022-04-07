<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Vercel;

use Exception;
use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Runtime;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Scaffolding
{
    /**
     * @var LambdaFunction
     */
    protected $function;

    public function __construct(LambdaFunction $function)
    {
        $this->function = $function;
    }

    public function entry()
    {
        return $this->configuration()['entry'];
    }

    public function files()
    {
        $scaffolding = $this->directory();

        return Finder::create($scaffolding)->selected()
            ->map(function ($file) use ($scaffolding) {
                $replacements = $this->replacements();

                // Swap all of the placeholders out in the scaffolding files.
                $contents = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    file_get_contents($file)
                );

                // Remove the base path.
                $name = Str::replace($scaffolding, '', $file);

                return [
                    'file' => $name,
                    'stream' => $contents,
                    'size' => strlen($contents),
                    'sha' => sha1($contents),
                ];
            });
    }

    public function directory()
    {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Scaffolding' . DIRECTORY_SEPARATOR . $this->configuration()['directory'];

        if (!is_dir($directory)) {
            throw new Exception('Unable to find Vercel Scaffolding.');
        }

        return $directory;
    }

    protected function replacements()
    {
        return [
            'sc_replace__handler_file' => explode('.', $this->function->normalizedHandler())[0],
            'sc_replace__handler_function' => explode('.', $this->function->normalizedHandler())[1],
            'sc_replace__middleware_token' => $this->secret
        ];
    }

    protected function configuration()
    {
        $config = Arr::get($this->configurations(), $this->function->runtime());

        if ($config) {
            throw new Exception("Unable to find Vercel scaffolding for the `{$this->function->runtime()}` runtime.");
        }

        return $config;
    }

    protected function configurations()
    {
        return [
            Runtime::NODEJS_14 => $this->js(),
            Runtime::NODEJS_12 => $this->js(),
            Runtime::NODEJS_10 => $this->js(),
        ];
    }

    protected function js()
    {
        return [
            'entry' => 'api/index.js',
            'directory' => 'js'
        ];
    }

}