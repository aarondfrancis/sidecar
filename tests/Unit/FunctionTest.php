<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Package;
use Hammerstone\Sidecar\Tests\Unit\Support\EmptyTestFunction;
use Hammerstone\Sidecar\Tests\Unit\Support\FakeStreamWrapper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;

class FunctionTest extends BaseTest
{

    /** @test */
    public function app_name_with_a_space_gets_dashed()
    {
        config(['app.name' => 'Amazing App']);

        $this->assertEquals('SC-Amazing-App-testing-', (new EmptyTestFunction)->prefix());
    }

}
