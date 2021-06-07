<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Aws\Lambda\Exception\LambdaException;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Events\AfterFunctionsActivated;
use Hammerstone\Sidecar\Events\AfterFunctionsDeployed;
use Hammerstone\Sidecar\Events\BeforeFunctionsActivated;
use Hammerstone\Sidecar\Events\BeforeFunctionsDeployed;
use Hammerstone\Sidecar\Exceptions\ConfigurationException;
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
    public static function make($functions = null)
    {
        return new static($functions);
    }

    /**
     * @param $functions
     */
    public function __construct($functions = null)
    {
        $this->lambda = app(LambdaClient::class);

        $this->functions = Sidecar::instantiatedFunctions($functions);

        if (empty($this->functions)) {
            throw new ConfigurationException(
                "Cannot deploy, no Sidecar functions have been configured. \n" .
                "Please check your config/sidecar.php file to ensure you have properly registered your functions. \n" .
                'Read more at https://hammerstone.dev/sidecar/docs/main/configuration#registering-functions'
            );
        }
    }

    /**
     * Deploy the code to Lambda. Creating or updating
     * functions where necessary.
     *
     * @param $activate
     */
    public function deploy($activate)
    {
        event(new BeforeFunctionsDeployed($this->functions));

        foreach ($this->functions as $function) {
            $this->deploySingle($function, $activate);
        }

        event(new AfterFunctionsDeployed($this->functions));

        if ($activate) {
            $this->activate();
        }
    }

    /**
     * Activate the latest versions of each function.
     */
    public function activate()
    {
        event(new BeforeFunctionsActivated($this->functions));

        foreach ($this->functions as $function) {
            $function->beforeActivation();

            $this->aliasLatestVersion($function);

            $function->afterActivation();
        }

        event(new AfterFunctionsActivated($this->functions));
    }

    protected function deploySingle($function)
    {
        Sidecar::log('---------');
        Sidecar::log('Deploying ' . get_class($function) . ' to Lambda. (Runtime ' . $function->runtime() . ')');

        $function->beforeDeployment();

        $this->functionExists($function)
            ? $this->updateExistingFunction($function)
            : $this->createNewFunction($function);

        $function->afterDeployment();

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

        $checksum = substr(md5(json_encode($config)), 0, 8);

        if ($this->functionExists($function, $checksum)) {
            return Sidecar::log('Function code and configuration are unchanged! Not updating anything.');
        }

        // Add the checksum to the description, so we can look for it next time.
        $config['Description'] .= " [$checksum]";

        $this->lambda->updateFunctionConfiguration(Arr::only($config, [
            'FunctionName',
            'Role',
            'Handler',
            'Description',
            'Timeout',
            'MemorySize',
        ]));

        Sidecar::log('Configuration updated.');

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

        $this->lambda->updateFunctionCode($config);

        Sidecar::log('Code updated.');
    }

    /**
     * @param LambdaFunction $function
     */
    protected function aliasLatestVersion(LambdaFunction $function)
    {
        $last = last($this->getLastVersionPage($function)['Versions'])['Version'];

        Sidecar::log("Activating the latest version ($last) of " . $function->nameWithPrefix() . '.');

        $this->lambda->deleteAlias([
            'FunctionName' => $function->nameWithPrefix(),
            'Name' => 'active',
        ]);

        $this->lambda->createAlias([
            'FunctionName' => $function->nameWithPrefix(),
            'FunctionVersion' => $last,
            'Name' => 'active',
        ]);
    }

    /**
     * @param LambdaFunction $function
     * @param null|string $marker
     * @return \Aws\Result
     */
    protected function getLastVersionPage(LambdaFunction $function, $marker = null)
    {
        $result = $this->lambda->listVersionsByFunction([
            'FunctionName' => $function->nameWithPrefix(),
            'MaxItems' => 100,
            'Marker' => $marker,
        ]);

        if ($marker = Arr::get($result, 'NextMarker')) {
            $result = $this->getLastVersionPage($function, $marker);
        }

        return $result;
    }

    /**
     * @param LambdaFunction $function
     * @param null $checksum
     * @return bool
     */
    protected function functionExists(LambdaFunction $function, $checksum = null)
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
