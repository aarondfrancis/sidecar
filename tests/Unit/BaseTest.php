<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Providers\SidecarServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
    */
    protected function defineEnvironment($app)
    {
        $app['config']->set('sidecar.app_name', 'Laravel');
    }

    protected function getPackageProviders($app)
    {
        return [
            SidecarServiceProvider::class
        ];
    }
}
