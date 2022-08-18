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
    public function sidecar_app_name_ignores_laravel_app_name()
    {
        config([
            'app.name' => 'Laravel',
            'sidecar.app_name' => 'Sidecar',
        ]);

        $this->assertEquals(
            'SC-Sidecar-testing--Sidecar-Tests-Unit-Support-EmptyTestFunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );

        config([
            'app.name' => 'Hammerstone',
        ]);

        $this->assertEquals(
            'SC-Sidecar-testing--Sidecar-Tests-Unit-Support-EmptyTestFunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );

        config([
            'sidecar.app_name' => 'Laravel',
        ]);

        $this->assertEquals(
            'SC-Laravel-testing--Sidecar-Tests-Unit-Support-EmptyTestFunction',
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
