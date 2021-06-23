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
}
