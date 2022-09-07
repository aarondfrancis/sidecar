<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Exception;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Events\AfterFunctionsActivated;
use Hammerstone\Sidecar\Events\AfterFunctionsDeployed;
use Hammerstone\Sidecar\Events\BeforeFunctionsActivated;
use Hammerstone\Sidecar\Events\BeforeFunctionsDeployed;
use Hammerstone\Sidecar\Exceptions\NoFunctionsRegisteredException;

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
     *
     * @throws NoFunctionsRegisteredException
     */
    public static function make($functions = null)
    {
        return new static($functions);
    }

    /**
     * @param $functions
     *
     * @throws NoFunctionsRegisteredException
     */
    public function __construct($functions = null)
    {
        $this->lambda = app(LambdaClient::class);

        $this->functions = Sidecar::instantiatedFunctions($functions);

        if (empty($this->functions)) {
            throw new NoFunctionsRegisteredException;
        }
    }

    /**
     * Deploy the code to Lambda. Creating or updating
     * functions where necessary.
     *
     * @return Deployment
     *
     * @throws Exception
     */
    public function deploy()
    {
        event(new BeforeFunctionsDeployed($this->functions));

        foreach ($this->functions as $function) {
            Sidecar::log('Deploying ' . get_class($function) . ' to Lambda as `' . $function->nameWithPrefix() . '`.');

            Sidecar::sublog(function () use ($function) {
                $this->deploySingle($function);
                $this->sweep($function);
            });
        }

        event(new AfterFunctionsDeployed($this->functions));

        return $this;
    }

    /**
     * Activate the latest versions of each function.
     *
     * @param  bool  $prewarm
     * @return Deployment
     */
    public function activate($prewarm = false)
    {
        event(new BeforeFunctionsActivated($this->functions));

        foreach ($this->functions as $function) {
            Sidecar::log('Activating function ' . get_class($function) . '.');
            Sidecar::sublog(function () use ($function, $prewarm) {
                $this->activateSingle($function, $prewarm);
            });
        }

        event(new AfterFunctionsActivated($this->functions));

        return $this;
    }

    /**
     * @param  LambdaFunction  $function
     *
     * @throws Exception
     */
    protected function deploySingle(LambdaFunction $function)
    {
        Sidecar::log('Environment: ' . Sidecar::getEnvironment());
        Sidecar::log('Architecture: ' . $function->architecture());
        Sidecar::log('Package Type: ' . $function->packageType());
        if ($function->packageType() === 'Zip') {
            Sidecar::log('Runtime: ' . $function->runtime());
        }

        $function->beforeDeployment();

        $this->lambda->functionExists($function)
            ? $this->updateExistingFunction($function)
            : $this->createNewFunction($function);

        $function->afterDeployment();
    }

    /**
     * @param  LambdaFunction  $function
     * @param  bool  $prewarm
     */
    protected function activateSingle(LambdaFunction $function, $prewarm)
    {
        $function->beforeActivation();

        $this->setEnvironmentVariables($function);

        if ($prewarm) {
            $this->warmLatestVersion($function);
        }

        $this->aliasLatestVersion($function);

        $function->afterActivation();
    }

    /**
     * @param  LambdaFunction  $function
     *
     * @throws Exception
     */
    protected function createNewFunction(LambdaFunction $function)
    {
        Sidecar::log('Creating new lambda function.');

        $this->lambda->createFunction($function->toDeploymentArray());
    }

    /**
     * @param  LambdaFunction  $function
     *
     * @throws Exception
     */
    protected function updateExistingFunction(LambdaFunction $function)
    {
        Sidecar::log('Function already exists, potentially updating code and configuration.');

        if ($this->lambda->updateExistingFunction($function) === LambdaClient::NOOP) {
            Sidecar::log('Function code and configuration are unchanged. Not updating anything.');
        } else {
            Sidecar::log('Function code and configuration updated.');
        }
    }

    /**
     * Add environment variables to the Lambda function, if they are provided.
     *
     * @param  LambdaFunction  $function
     */
    protected function setEnvironmentVariables(LambdaFunction $function)
    {
        if (!is_array($function->variables())) {
            return Sidecar::log('Environment variables not managed by Sidecar. Skipping.');
        }

        $this->lambda->updateFunctionVariables($function);
    }

    /**
     * Send warming requests to the latest version.
     *
     * @param  LambdaFunction  $function
     */
    protected function warmLatestVersion(LambdaFunction $function)
    {
        $this->lambda->waitUntilFunctionUpdated($function);

        if ($this->lambda->latestVersionHasAlias($function, 'active')) {
            Sidecar::log('Active version unchanged, no need to warm.');

            return;
        }

        $version = $this->lambda->getLatestVersion($function);

        Sidecar::log("Warming Version $version of {$function->nameWithPrefix()}...");

        // Warm the latest version of the function, waiting for the results
        // to settle. If we didn't wait for the results to settle, we might
        // activate them immediately while they are still warming.
        $results = Sidecar::warmSingle($function, $async = false, $version);

        if ($warmed = count($results)) {
            Sidecar::log("Warmed $warmed instances.");
        } else {
            Sidecar::log('No instances warmed. If this is unexpected, confirm your `warmingConfig` method is set up correctly.');
        }
    }

    /**
     * Alias the latest version of a function as the "active" one.
     *
     * @param  LambdaFunction  $function
     */
    protected function aliasLatestVersion(LambdaFunction $function)
    {
        $version = $this->lambda->getLatestVersion($function);
        $result = $this->lambda->aliasVersion($function, 'active', $version);

        $messages = [
            LambdaClient::CREATED => "Creating alias for Version $version of {$function->nameWithPrefix()}.",
            LambdaClient::UPDATED => "Activating Version $version of {$function->nameWithPrefix()}.",
            LambdaClient::NOOP => "Version $version of {$function->nameWithPrefix()} is already active.",
        ];

        Sidecar::log($messages[$result]);
    }

    /**
     * Remove old, outdated versions of a function.
     *
     * @param  LambdaFunction  $function
     */
    protected function sweep(LambdaFunction $function)
    {
        $versions = $this->lambda->getVersions($function);

        $keep = 20;

        // We want to leave `$keep` real versions and the $LATEST
        // version that AWS creates. The $LATEST version isn't a
        // unique one, but it always comes back from the API.
        if (count($versions) < ($keep + 1)) {
            return;
        }

        // Skip the $LATEST at the beginning and remove the
        // `$keep` good ones at the end.
        $outdated = array_splice($versions, 1, -$keep);

        // Only do five at a time for each function, as
        // we catch up from having no sweeping at all.
        $outdated = array_slice($outdated, 0, 5);

        foreach ($outdated as $version) {
            $version = $version['Version'];
            Sidecar::log("Removing outdated version $version.");

            $this->lambda->deleteFunctionVersion($function, $version);
        }
    }
}
