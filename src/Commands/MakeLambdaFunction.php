<?php

/**
 * @author Wilsen Hernández <wilsenforwork@gmail.com|https://github.com/wilsenhc>
 */

namespace Hammerstone\Sidecar\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:lambda-function')]
class MakeLambdaFunction extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:lambda-function {name} {--runtime=nodejs20.x : The runtime that will be used to create the lambda function}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Sidecar Lambda function class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Lambda';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/lambda-function.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\Sidecar";
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $runtime = $this->option('runtime') ?: 'nodejs20.x';

        return str_replace(['nodejs20.x', '{{ runtime }}'], $runtime, $stub);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['runtime', null, InputOption::VALUE_OPTIONAL, 'The runtime that will be used to create the lambda function', 'nodejs20.x'],
        ];
    }
}
