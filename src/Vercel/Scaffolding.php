<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Vercel;

use Exception;
use Hammerstone\Sidecar\Exceptions\ConfigurationException;
use Hammerstone\Sidecar\Finder;
use Hammerstone\Sidecar\Runtime;
use Hammerstone\Sidecar\ServerlessFunction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Scaffolding
{
    /**
     * @var ServerlessFunction
     */
    protected $function;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @param  ServerlessFunction  $function
     *
     * @throws Exception
     */
    public function __construct(ServerlessFunction $function)
    {
        $this->function = $function;
        $this->configuration = $this->configuration();
    }

    public function entry()
    {
        return $this->configuration['entry'];
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
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Scaffolding' . DIRECTORY_SEPARATOR . $this->configuration['directory'];

        if (!is_dir($directory)) {
            throw new Exception('Unable to find Vercel Scaffolding.');
        }

        return $directory;
    }

    protected function replacements()
    {
        $handler = $this->function->normalizedHandler();

        if (!$handler) {
            throw new ConfigurationException('Handler not set.');
        }

        $handler = explode('.', $handler);

        return [
            'sc_replace__handler_file' => $handler[0],
            'sc_replace__handler_function' => $handler[1],
            'sc_replace__middleware_token' => config('sidecar.vercel_signing_secret'),
            'sc_replace__runtime_version' => $this->configuration['runtime_version']
        ];
    }

    protected function configuration()
    {
        $config = Arr::get($this->configurations(), $this->function->runtime());

        if (!$config) {
            throw new Exception("Unable to find Vercel scaffolding for the `{$this->function->runtime()}` runtime.");
        }

        return $config;
    }

    protected function configurations()
    {
        return [
            Runtime::NODEJS_14 => $this->js('14.x'),
            Runtime::NODEJS_12 => $this->js('12.x'),
            Runtime::NODEJS_10 => $this->js('10.x'),
        ];
    }

    protected function js($runtime)
    {
        return [
            'entry' => 'api/index.js',
            'directory' => 'js',
            'runtime_version' => $runtime,
        ];
    }
}
