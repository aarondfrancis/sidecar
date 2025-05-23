<?php

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Sidecar;

class EnvironmentTest extends Base
{
    public function test_it_can_be_overridden()
    {
        $this->assertEquals('testing', Sidecar::getEnvironment());

        Sidecar::overrideEnvironment('production');

        $this->assertEquals('production', Sidecar::getEnvironment());

        Sidecar::clearEnvironment();

        $this->assertEquals('testing', Sidecar::getEnvironment());
    }

    public function test_precedence_is_correct()
    {
        Sidecar::clearEnvironment();

        config([
            'sidecar.env' => null,
            'app.env' => 'testing'
        ]);

        $this->assertEquals('testing', Sidecar::getEnvironment());

        config(['sidecar.env' => 'sidecar_env']);

        $this->assertEquals('sidecar_env', Sidecar::getEnvironment());

        Sidecar::overrideEnvironment('overridden');

        $this->assertEquals('overridden', Sidecar::getEnvironment());
    }
}
