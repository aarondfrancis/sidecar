<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests;

use Hammerstone\Sidecar\Sidecar;

class EnvironmentTest extends BaseTest
{
    /** @test */
    public function it_can_be_overridden()
    {
        $this->assertEquals('testing', Sidecar::getEnvironment());

        Sidecar::overrideEnvironment('production');

        $this->assertEquals('production', Sidecar::getEnvironment());

        Sidecar::clearEnvironment();

        $this->assertEquals('testing', Sidecar::getEnvironment());
    }
}
