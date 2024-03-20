<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Sidecar;

class EnvironmentTest extends Base
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

    /** @test */
    public function precedence_is_correct()
    {
        Sidecar::clearEnvironment();
        
        config([
            'sidecar.env' => null,
            'app.env' => 'app_env'
        ]);

        $this->assertEquals('app_env', Sidecar::getEnvironment());

        config(['sidecar.env' => 'sidecar_env']);

        $this->assertEquals('sidecar_env', Sidecar::getEnvironment());

        Sidecar::overrideEnvironment('overridden');

        $this->assertEquals('overridden', Sidecar::getEnvironment());
    }
}
