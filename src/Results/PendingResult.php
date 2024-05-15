<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Results;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Hammerstone\Sidecar\LambdaFunction;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PendingResult implements Responsable, ResultContract
{
    /**
     * @var SettledResult
     */
    protected $settled;

    /**
     * @var PromiseInterface
     */
    protected $raw;

    /**
     * @var LambdaFunction
     */
    protected $function;

    /**
     * @param  PromiseInterface  $raw
     */
    public function __construct($raw, LambdaFunction $function)
    {
        $this->raw = $raw;
        $this->function = $function;
    }

    /**
     * @return SettledResult
     */
    public function settled()
    {
        if ($this->settled) {
            return $this->settled;
        }

        return $this->settled = $this->function->toSettledResult($this->raw->wait());
    }

    /**
     * @return PromiseInterface
     */
    public function rawPromise()
    {
        return $this->raw;
    }

    /**
     * Defer to the SettledResult.
     *
     * @param  Request  $request
     * @return Response
     *
     * @throws Exception
     */
    public function toResponse($request)
    {
        return $this->settled()->toResponse($request);
    }
}
