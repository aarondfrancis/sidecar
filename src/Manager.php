<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Aws\Lambda\Exception\LambdaException;
use Closure;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Concerns\HandlesLogging;
use Hammerstone\Sidecar\Concerns\ManagesEnvironments;
use Hammerstone\Sidecar\Events\AfterFunctionExecuted;
use Hammerstone\Sidecar\Events\BeforeFunctionExecuted;
use Hammerstone\Sidecar\Exceptions\FunctionNotFoundException;
use Hammerstone\Sidecar\Results\PendingResult;
use Hammerstone\Sidecar\Results\SettledResult;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Throwable;

class Manager
{
    use Macroable, HandlesLogging, ManagesEnvironments;

    /**
     * @var string
     */
    public $executionVersion = 'active';

    /**
     * @param $version
     * @param  null  $callback
     * @return Closure
     */
    public function overrideExecutionVersion($version, $callback = null)
    {
        $cached = $this->executionVersion;

        $undo = function () use ($cached) {
            $this->executionVersion = $cached;
        };

        $this->executionVersion = $version;

        if ($callback) {
            $result = $callback();
            $undo();

            return $result;
        }

        return $undo;
    }

    /**
     * @param  string|LambdaFunction  $function
     * @param  array  $payload
     * @param  bool  $async
     * @return PendingResult|SettledResult
     *
     * @throws Exceptions\SidecarException
     * @throws FunctionNotFoundException
     */
    public function execute($function, $payload = [], $async = false, $invocationType = 'RequestResponse')
    {
        // Could be a FQCN.
        if (is_string($function)) {
            $function = app($function);
        }

        $payload = $function->preparePayload($payload);

        if ($payload instanceof Arrayable) {
            $payload = $payload->toArray();
        }

        $method = $async ? 'invokeAsync' : 'invoke';

        event(new BeforeFunctionExecuted($function, $payload));

        $function->beforeExecution($payload);

        try {
            $result = app(LambdaClient::class)->{$method}([
                // Function name plus our alias name.
                'FunctionName' => $function->nameWithPrefix() . ':' . $this->executionVersion,

                // `RequestResponse` is a synchronous call, vs `Event` which
                // is a fire-and-forget, we can make it async by using the
                // invokeAsync method.
                'InvocationType' => $invocationType,

                // Include the execution log in the response.
                'LogType' => 'Tail',

                // Pass the payload to the function.
                'Payload' => json_encode($payload)
            ]);
        } catch (LambdaException $e) {
            if ($e->getStatusCode() === 404) {
                throw FunctionNotFoundException::make($function);
            }

            throw $e;
        }

        // Let the calling function determine what to do with the result.
        $result = $function->toResult($result);

        event(new AfterFunctionExecuted($function, $payload, $result));

        $function->afterExecution($payload, $result);

        return $result;
    }

    /**
     * @param $function
     * @param  array  $payload
     * @return PendingResult|Results\SettledResult
     *
     * @throws Exceptions\SidecarException
     * @throws FunctionNotFoundException
     */
    public function executeAsync($function, $payload = [])
    {
        return $this->execute($function, $payload, $async = true);
    }

    /**
     * @param $function
     * @param $payloads
     * @param  bool  $async
     * @return array
     *
     * @throws Exceptions\SidecarException
     * @throws FunctionNotFoundException
     */
    public function executeMany($function, $payloads, $async = false)
    {
        if (is_int($payloads)) {
            $payloads = array_fill(0, $payloads, []);
        }

        $results = array_map(function ($payload) use ($function) {
            return $this->execute($function, $payload, $async = true);
        }, $payloads);

        if ($async) {
            // Return all the Pending Results.
            return $results;
        }

        // Wait for all the requests to finish.
        return array_map(function ($result) {
            return $result->settled();
        }, $results);
    }

    /**
     * @param $params
     * @return array
     *
     * @throws Exceptions\SidecarException
     * @throws FunctionNotFoundException
     */
    public function executeManyAsync($params)
    {
        return $this->executeMany($params, $async = true);
    }

    /**
     * @param $function
     * @param  array  $payload
     * @return PendingResult|SettledResult
     *
     * @throws Exceptions\SidecarException
     * @throws FunctionNotFoundException
     */
    public function executeAsEvent($function, $payload = [])
    {
        return $this->execute($function, $payload, $async = false, $invocationType = 'Event');
    }

    /**
     * Get an array of instantiated functions.
     *
     * @param  null  $functions
     * @return array
     */
    public function instantiatedFunctions($functions = null)
    {
        $functions = Arr::wrap($functions ?? config('sidecar.functions'));

        return array_map(function ($function) {
            return is_string($function) ? app($function) : $function;
        }, $functions);
    }

    /**
     * Warm functions by firing a set of async requests at them.
     *
     * @param  null|array  $functions
     *
     * @throws Throwable
     */
    public function warm($functions = null)
    {
        array_map(function (LambdaFunction $function) {
            $this->warmSingle($function);
        }, $this->instantiatedFunctions($functions));
    }

    /**
     * Warm a single function, with the option to override the version.
     *
     * @param  LambdaFunction  $function
     * @param  bool  $async
     * @param  string  $version
     * @return array
     *
     * @throws Throwable
     */
    public function warmSingle(LambdaFunction $function, $async = true, $version = 'active')
    {
        $config = $function->warmingConfig();

        if (!$config instanceof WarmingConfig || !$config->instances) {
            return [];
        }

        $payloads = array_fill(0, $config->instances, $config->payload);

        return $this->overrideExecutionVersion($version, function () use ($function, $async, $payloads) {
            return $function::executeMany($payloads, $async);
        });
    }
}
