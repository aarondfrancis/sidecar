<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Providers\SidecarServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

abstract class Base extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetup($app)
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
