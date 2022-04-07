<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Results;

use GuzzleHttp\Promise\PromiseInterface;
use Hammerstone\Sidecar\ServerlessFunction;
use Illuminate\Contracts\Support\Responsable;

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
     * @var ServerlessFunction
     */
    protected $function;

    /**
     * @param  PromiseInterface  $raw
     * @param  ServerlessFunction  $function
     */
    public function __construct($raw, ServerlessFunction $function)
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
     * Defer to the SettledResult.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function toResponse($request)
    {
        return $this->settled()->toResponse($request);
    }
}
