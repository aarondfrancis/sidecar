<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Results\Arbiter;
use Hammerstone\Sidecar\Results\PendingResult;
use Throwable;

class Manager
{
    /**
     * @var array
     */
    protected $loggers = [];

    /**
     * @var string
     */
    protected $environment;

    /**
     * @param $closure
     * @return $this
     */
    public function addLogger($closure)
    {
        $this->loggers[] = $closure;

        return $this;
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        foreach ($this->loggers as $logger) {
            $logger('[Sidecar] ' . $message);
        }
    }

    /**
     * @param string $environment
     */
    public function overrideEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Clear the environment override.
     */
    public function clearEnvironment()
    {
        $this->environment = null;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment ?? config('app.env');
    }

    /**
     * @param string|LambdaFunction $function
     * @param array $payload
     * @param false $async
     * @return PendingResult|Results\SettledResult
     * @throws Exceptions\SidecarException
     */
    public function execute($function, $payload = [], $async = false)
    {
        // Could be a FQCN.
        if (is_string($function)) {
            $function = app($function);
        }

        $method = $async ? 'invokeAsync' : 'invoke';

        $result = app(LambdaClient::class)->{$method}([
            // Function name plus our alias name.
            'FunctionName' => $function->nameWithPrefix() . ':active',

            // `RequestResponse` is a synchronous call, vs `Event` which
            // is a fire-and-forget, we can make it async by using the
            // invokeAsync method.
            'InvocationType' => 'RequestResponse',

            // Include the execution log in the response.
            'LogType' => 'Tail',

            // Pass the payload to the function.
            'Payload' => json_encode($payload)
        ]);

        // Let the calling function determine what to do with the result.
        return $function->toResult($result);
    }

    /**
     * @param $function
     * @param array $payload
     * @return PendingResult|Results\SettledResult
     * @throws Exceptions\SidecarException
     */
    public function executeAsync($function, $payload = [])
    {
        return $this->execute($function, $payload, $async = true);
    }

    /**
     * @param $params
     * @param bool $async
     * @return array
     * @throws Exceptions\SidecarException
     */
    public function executeMany($params, $async = false)
    {
        $results = array_map(function ($param) {
            // A function with no payload.
            if (!is_array($param)) {
                $param = [
                    'function' => $param,
                    'payload' => null
                ];
            }

            return $this->execute($param['function'], $param['payload'], $async = true);
        }, $params);

        if ($async) {
            // Return all the Pending Results.
            return $results;
        }

        // Wait for all the requests to finish.
        return array_map(function($result) {
            return $result->settled();
        }, $results);
    }

    /**
     * @param $params
     * @return array
     * @throws Throwable
     */
    public function executeManyAsync($params)
    {
        return $this->executeMany($params, $async = true);
    }
}
