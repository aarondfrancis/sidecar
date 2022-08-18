<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Tests\Unit\Support\EmptyTestFunction;

class FunctionTest extends BaseTest
{
    /** @test */
    public function app_name_with_a_space_gets_dashed()
    {
        config([
            'sidecar.app_name' => 'Amazing App'
        ]);

        $this->assertEquals(
            'SC-Amazing-App-testing-ecar-Tests-Unit-Support-EmptyTestFunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );
    }

    /** @test */
    public function app_name_defaults_to_laravel_app_name_when_not_defined()
    {
        config([
            'app.name' => 'Amazing App',
            'sidecar.app_name' => null,
        ]);

        $this->assertEquals(
            'SC-Amazing-App-testing-ecar-Tests-Unit-Support-EmptyTestFunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );

        config([
            'app.name' => 'Laravel',
            'sidecar.app_name' => 'Much Better App'
        ]);

        $this->assertEquals(
            'SC-Much-Better-App-testing-ecar-Tests-Unit-Support-EmptyTestFunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );
    }

    /** @test */
    public function memory_and_timeout_and_storage_get_cast_to_ints()
    {
        config([
            'sidecar.timeout' => '5',
            'sidecar.memory' => '500',
            'sidecar.storage' => '1024'
        ]);

        $array = (new EmptyTestFunction)->toDeploymentArray();

        $this->assertSame(5, $array['Timeout']);
        $this->assertSame(500, $array['MemorySize']);
        $this->assertSame(1024, $array['EphemeralStorage']['Size']);
    }
}
