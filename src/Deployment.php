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
            throw new NoFunctionsRegisteredException;
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
            Sidecar::log('Deploying ' . get_class($function) . ' to Lambda as "' . $function->nameWithPrefix(). '."');
            $undo = Sidecar::sublog();

            $this->deploySingle($function, $activate);
            $this->sweep($function);

            $undo();
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

    /**
     * @param LambdaFunction $function
     * @throws Exception
     */
    protected function deploySingle(LambdaFunction $function)
    {
        Sidecar::log('Environment: ' . Sidecar::getEnvironment());
        Sidecar::log('Runtime: ' . $function->runtime());

        $function->beforeDeployment();

        $this->lambda->functionExists($function)
            ? $this->updateExistingFunction($function)
            : $this->createNewFunction($function);

        $function->afterDeployment();
    }

    /**
     * @param LambdaFunction $function
     * @throws Exception
     */
    protected function createNewFunction(LambdaFunction $function)
    {
        Sidecar::log('Creating new lambda function.');

        $this->lambda->createFunction($function->toDeploymentArray());
    }

    /**
     * @param LambdaFunction $function
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
     * Alias the latest version of a function as the "active" one.
     *
     * @param LambdaFunction $function
     */
    protected function aliasLatestVersion(LambdaFunction $function)
    {
        $version = $this->lambda->getLatestVersion($function);
        $result = $this->lambda->aliasVersion($function, 'active', $version);

        $messages = [
            LambdaClient::CREATED => "Creating alias for latest version ($version) of {$function->nameWithPrefix()}.",
            LambdaClient::UPDATED => "Activating latest version ($version) of {$function->nameWithPrefix()}.",
            LambdaClient::NOOP => "Version $version of {$function->nameWithPrefix()} is already active.",
        ];

        Sidecar::log($messages[$result]);
    }

    /**
     * Remove old, outdated versions of a function.
     *
     * @param LambdaFunction $function
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
        // ten good ones at the end.
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
