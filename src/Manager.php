<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Hammerstone\Sidecar\Exceptions\SidecarException;
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
     * @param null $function
     */
    public function deploy($function = null)
    {
        Deployment::make($function)->deploy();
    }

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
     * @return string
     * @throws SidecarException
     */
    public function executionRole()
    {
        $arn = config('sidecar.role_arn');

        if ($arn !== 'CONSTRUCT_FROM_VAPOR_VARIABLES') {
            return $arn;
        }

        if (env('VAPOR_SSM_PATH')) {
            throw new SidecarException('Not running in Vapor. Unable to determine Sidecar execution ARN.');
        }

        $sqs = env('SQS_PREFIX');

        if (!$sqs) {
            throw new SidecarException('Vapor SQS prefix is not available. Unable to determine Sidecar execution ARN.');
        }

        $account = last(explode('/', $sqs));

        return "arn:aws:iam::$account:role/laravel-vapor-role";
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
            'FunctionName' => $function->nameWithPrefix(),

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
     * @param $params
     * @param bool $wait
     * @return array
     * @throws Throwable
     */
    public function executeMany($params, $wait = true)
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

        if ($wait) {
            // Wait for all the requests to finish.
            $results = array_map('settled', $results);
        }

        return $results;
    }
}
