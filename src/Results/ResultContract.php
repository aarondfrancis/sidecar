<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Results;

use Hammerstone\Sidecar\ServerlessFunction;

interface ResultContract
{
    /**
     * @param $raw
     * @param  ServerlessFunction  $function
     */
    public function __construct($raw, ServerlessFunction $function);

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($response);

    /**
     * @return SettledResult
     */
    public function settled();
}
