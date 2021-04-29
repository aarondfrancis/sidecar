<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Aws\Lambda\Exception\LambdaException;
use Hammerstone\Sidecar\Events\AfterFunctionsDeploy;
use Hammerstone\Sidecar\Events\BeforeFunctionsDeploy;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Deployment
{
    /**
     * @var array
     */
    protected $functions;

    /**
     * @var LambdaClient
     */
    protected $lambda;

    /**
     * @param $functions
     * @return static
     */
    public static function make($functions)
    {
        return new static($functions);
    }

    /**
     * @param $functions
     */
    public function __construct($functions)
    {
        $this->lambda = app(LambdaClient::class);

        // If the developer hasn't passed a single
        // function, then deploy all of them.
        if (is_null($functions)) {
            $functions = config('sidecar.functions');
        }

        $this->functions = Arr::wrap($functions);
    }

    public function deploy()
    {
        event(new BeforeFunctionsDeploy($this->functions));

        for ($i = 0; $i < count($this->functions); $i++) {
            $this->deploySingle($this->functions[$i], $i + 1, count($this->functions));
        }

        event(new AfterFunctionsDeploy($this->functions));
    }

    protected function deploySingle($function, $index, $total)
    {
        if (is_string($function)) {
            $function = app($function);
        }

        Sidecar::log('---------');
        Sidecar::log('Deploying ' . get_class($function) . ' to Lambda. (Runtime ' . $function->runtime() . '.)');

        $function->beforeDeployment($index, $total);

        $this->functionExists($function)
            ? $this->updateExistingFunction($function)
            : $this->createNewFunction($function);

        $function->afterDeployment($index, $total);

        Sidecar::log('---------');
    }

    /**
     * @param LambdaFunction $function
     * @return \Aws\Result
     * @throws \Exception
     */
    protected function createNewFunction(LambdaFunction $function)
    {
        Sidecar::log('Creating new lambda function.');

        return $this->lambda->createFunction($function->toDeploymentArray());
    }

    /**
     * @param LambdaFunction $function
     * @throws \Exception
     */
    protected function updateExistingFunction(LambdaFunction $function)
    {
        Sidecar::log('Function already exists, potentially updating code and configuration.');

        $config = $function->toDeploymentArray();

        $check = '[' . substr(md5(json_encode($config)), 0, 16) . ']';

        if ($this->functionExists($function, $check)) {
            return Sidecar::log('Function code and configuration are unchanged! Not updating anything.');
        }

        // Add the checksum to the description, so we can look for it next time.
        $config['Description'] .= " $check";

        $result = $this->lambda->updateFunctionConfiguration(Arr::only($config, [
            'FunctionName',
            'Role',
            'Handler',
            'Description',
            'Timeout',
            'MemorySize',
        ]));

        $config = Arr::only($config, [
            'FunctionName',
            'Code',
            'Publish',
        ]);

        // For the updateFunctionCode call, AWS requires that the S3Bucket
        // and S3Key be top level instead of nested under `Code`.
        $config['S3Bucket'] = $config['Code']['S3Bucket'];
        $config['S3Key'] = $config['Code']['S3Key'];

        unset($config['Code']);

        $result = $this->lambda->updateFunctionCode($config);
    }

    /**
     * @param $function
     * @param null $checksum
     * @return bool
     */
    protected function functionExists($function, $checksum = null)
    {
        try {
            $response = $this->lambda->getFunction([
                'FunctionName' => $function->nameWithPrefix(),
            ]);
        } catch (LambdaException $e) {
            // If it's a 404, then that means the function doesn't
            // exist, which is what we're trying to figure out.
            if ($e->getStatusCode() === 404) {
                return false;
            }

            // If it's some other kind of error, we need to bail.
            throw $e;
        }

        // We're just checking to see if any version of it exists,
        // not necessarily a particular version.
        if (is_null($checksum)) {
            return true;
        }

        $description = Arr::get($response, 'Configuration.Description');

        // No description? Default to re-deploying.
        if (!$description) {
            return false;
        }

        // See if the description contains the checksum.
        return Str::contains($description, $checksum);
    }
}
