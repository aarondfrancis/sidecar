<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Results;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface ResultContract
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse(mixed $response);

    public function settled(): SettledResult;
}
