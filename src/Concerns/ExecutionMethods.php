<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Concerns;

use Hammerstone\Sidecar\Results\PendingResult;
use Hammerstone\Sidecar\Results\SettledResult;
use Hammerstone\Sidecar\Sidecar;

trait ExecutionMethods
{
    /**
     * Execute the current function and return the response.
     *
     * @param  array  $payload
     * @param  bool  $async
     * @return SettledResult|PendingResult
     */
    public static function execute($payload = [], $async = false, $invocationType = 'RequestResponse')
    {
        return Sidecar::execute(static::class, $payload, $async, $invocationType);
    }

    /**
     * Execute the current function and return the response.
     *
     * @param  array  $payload
     * @return PendingResult
     */
    public static function executeAsync($payload = [])
    {
        return static::execute($payload, $async = true);
    }

    /**
     * Execute the current function and return the response.
     *
     * @param $payloads
     * @param  bool  $async
     * @return array
     *
     * @throws \Throwable
     */
    public static function executeMany($payloads, $async = false)
    {
        return Sidecar::executeMany(static::class, $payloads, $async);
    }

    /**
     * Execute the current function and return the response.
     *
     * @param $payloads
     * @return array
     *
     * @throws \Throwable
     */
    public static function executeManyAsync($payloads)
    {
        return static::executeMany($payloads, $async = true);
    }

    /**
     * Execute the current function asynchronously as an event. This is "fire-and-forget" style.
     *
     * @param  array  $payload
     * @return PendingResult
     */
    public static function executeAsEvent($payload = [])
    {
        return static::execute($payload, $async = false, $invocationType = 'Event');
    }
}
