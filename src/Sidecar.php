<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Illuminate\Support\Facades\Facade;

class Sidecar extends Facade
{
    /**
     * @see Manager
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Manager::class;
    }
}
