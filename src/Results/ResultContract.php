<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Results;

use Hammerstone\Sidecar\LambdaFunction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface ResultContract
{
    public function __construct($raw, LambdaFunction $function);

    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($response);

    /**
     * @return SettledResult
     */
    public function settled();
}
