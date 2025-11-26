<?php

declare(strict_types=1);

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
    protected ?SettledResult $settled = null;

    public function __construct(
        protected PromiseInterface $raw,
        protected LambdaFunction $function
    ) {}

    public function settled(): SettledResult
    {
        if ($this->settled) {
            return $this->settled;
        }

        return $this->settled = $this->function->toSettledResult($this->raw->wait());
    }

    public function rawPromise(): PromiseInterface
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
    public function toResponse(mixed $request)
    {
        return $this->settled()->toResponse($request);
    }
}
