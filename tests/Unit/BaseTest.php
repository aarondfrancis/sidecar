<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Dotenv\Dotenv;
use Hammerstone\Sidecar\Providers\SidecarServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Since we have live Vercel and AWS keys for integration tests, we can't
        // use PHPUnit's environment handling as those would be exposed in the
        // git repository. Here we manually load any values from a .env file
        // for local tests. In GitHub there is no .env, so we use safeLoad.
        Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
    }

    protected function getPackageProviders($app)
    {
        return [
            SidecarServiceProvider::class
        ];
    }
}
