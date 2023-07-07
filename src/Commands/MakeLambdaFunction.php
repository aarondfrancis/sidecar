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
    protected $signature = 'make:lambda-function';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new Sidecar Lambda function class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'LambdaFunction';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return base_path('stubs/lambda-function.stub');
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

        $runtime = $this->option('runtime') ?: 'nodejs18.x';

        return str_replace(['nodejs18.x', '{{ runtime }}'], $runtime, $stub);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the lambda function already exists'],
            ['runtime', null, InputOption::VALUE_OPTIONAL, 'The runtime that will be used to create the lambda function'],
        ];
    }
}