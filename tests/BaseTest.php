<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests;

use Hammerstone\Sidecar\Providers\SidecarServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    public $loadEnvironmentVariables = true;

    protected function resolveApplication()
    {
        // Since we have live Vercel and AWS keys for integration tests, we can't
        // use PHPUnit's environment handling as those would be exposed in the
        // git repository. We use an ignored .env file for our local tests.
        return parent::resolveApplication()->useEnvironmentPath(dirname(__DIR__));
    }

    protected function getPackageProviders($app)
    {
        return [
            SidecarServiceProvider::class
        ];
    }
}
