<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Airdrop\Tests;

use Hammerstone\Sidecar\Providers\SidecarServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            SidecarServiceProvider::class
        ];
    }

}