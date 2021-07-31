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
            'app.name' => 'Amazing App'
        ]);

        $this->assertEquals(
            'SC-Amazing-App-testing-ecar-Tests-Unit-Support-EmptyTestFunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );
    }

    /** @test */
    public function memory_and_timeout_get_cast_to_ints()
    {
        config([
            'sidecar.timeout' => '5',
            'sidecar.memory' => '500'
        ]);

        $array = (new EmptyTestFunction)->toDeploymentArray();

        $this->assertSame(5, $array['Timeout']);
        $this->assertSame(500, $array['MemorySize']);
    }

}
