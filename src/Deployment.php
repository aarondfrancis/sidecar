<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Exception;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Clients\VercelClient;
use Hammerstone\Sidecar\Contracts\FaasClient;
use Hammerstone\Sidecar\Events\AfterFunctionsActivated;
use Hammerstone\Sidecar\Events\AfterFunctionsDeployed;
use Hammerstone\Sidecar\Events\BeforeFunctionsActivated;
use Hammerstone\Sidecar\Events\BeforeFunctionsDeployed;
use Hammerstone\Sidecar\Exceptions\NoFunctionsRegisteredException;
use Hammerstone\Sidecar\Vercel\Client;

class Deployment
{
    /**
     * @var array
     */
    protected $functions;

    /**
     * @var FaasClient
     */
    protected $client;

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
        $this->client = app(LambdaClient::class);
        $this->client = app(VercelClient::class);

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
            // @TODO insert platform name
            Sidecar::log('Deploying ' . get_class($function) . ' as `' . $function->nameWithPrefix() . '`.');

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
     * @param  ServerlessFunction  $function
     *
     * @throws Exception
     */
    protected function deploySingle(ServerlessFunction $function)
    {
        Sidecar::log('Environment: ' . Sidecar::getEnvironment());
        Sidecar::log('Package Type: ' . $function->packageType());
        if ($function->packageType() === 'Zip') {
            Sidecar::log('Runtime: ' . $function->runtime());
        }

        $function->beforeDeployment();

        $this->client->functionExists($function)
            ? $this->updateExistingFunction($function)
            : $this->createNewFunction($function);

        $function->afterDeployment();
    }

    /**
     * @param  ServerlessFunction  $function
     * @param  bool  $prewarm
     */
    protected function activateSingle(ServerlessFunction $function, $prewarm)
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
     * @param  ServerlessFunction  $function
     *
     * @throws Exception
     */
    protected function createNewFunction(ServerlessFunction $function)
    {
        Sidecar::log('Creating new lambda function.');

        $this->client->createFunction($function->toDeploymentArray());
    }

    /**
     * @param  ServerlessFunction  $function
     *
     * @throws Exception
     */
    protected function updateExistingFunction(ServerlessFunction $function)
    {
        Sidecar::log('Function already exists, potentially updating code and configuration.');

        if ($this->client->updateExistingFunction($function) === FaasClient::NOOP) {
            Sidecar::log('Function code and configuration are unchanged. Not updating anything.');
        } else {
            Sidecar::log('Function code and configuration updated.');
        }
    }

    /**
     * Add environment variables to the function, if they are provided.
     *
     * @param  ServerlessFunction  $function
     */
    protected function setEnvironmentVariables(ServerlessFunction $function)
    {
        if (!is_array($function->variables())) {
            return Sidecar::log('Environment variables not managed by Sidecar. Skipping.');
        }

        $this->client->updateFunctionVariables($function);
    }

    /**
     * Send warming requests to the latest version.
     *
     * @param  ServerlessFunction  $function
     */
    protected function warmLatestVersion(ServerlessFunction $function)
    {
        $this->client->waitUntilFunctionUpdated($function);

        if ($this->client->latestVersionHasAlias($function, 'active')) {
            Sidecar::log('Active version unchanged, no need to warm.');

            return;
        }

        $version = $this->client->getLatestVersion($function);

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
     * @param  ServerlessFunction  $function
     */
    protected function aliasLatestVersion(ServerlessFunction $function)
    {
        $version = $this->client->getLatestVersion($function);
        $result = $this->client->aliasVersion($function, 'active', $version);

        $messages = [
            FaasClient::CREATED => "Creating alias for Version $version of {$function->nameWithPrefix()}.",
            FaasClient::UPDATED => "Activating Version $version of {$function->nameWithPrefix()}.",
            FaasClient::NOOP => "Version $version of {$function->nameWithPrefix()} is already active.",
        ];

        Sidecar::log($messages[$result]);
    }

    /**
     * Remove old, outdated versions of a function.
     *
     * @param  ServerlessFunction  $function
     */
    protected function sweep(ServerlessFunction $function)
    {
        $versions = $this->client->getVersions($function);

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

            $this->client->deleteFunctionVersion($function, $version);
        }
    }
}
