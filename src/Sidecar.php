<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Illuminate\Support\Facades\Facade;

class Sidecar extends Facade
{
    /**
     * @see \Hammerstone\Sidecar\Manager
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Manager::class;
    }
}
