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
            'sc-amazing-app-testing-7a7a-tests-unit-support-emptytestfunction',
            (new EmptyTestFunction)->nameWithPrefix()
        );
    }

    /** @test */
    public function function_names_over_64_get_limited_to_64()
    {
        config([
            'app.name' => 'Amazing App Amazing App Amazing App Amazing App Amazing App Amazing App'
        ]);

        $function = new EmptyTestFunction;
        $name = $function->nameWithPrefix();

        $this->assertGreaterThan(32, strlen($function->name()));
        $this->assertEquals(64, strlen($name));
        $this->assertEquals('sc-amazing-app-amazing-app-a0528-7a7at-support-emptytestfunction', $name);
    }

    /** @test */
    public function short_prefix_means_longer_name()
    {
        config([
            'app.name' => 'A'
        ]);

        $function = new EmptyTestFunction;
        $name = $function->nameWithPrefix();

        $this->assertEquals(64, strlen($name));
        $this->assertEquals('sc-a-testing-7a7ane-sidecar-tests-unit-support-emptytestfunction', $name);
    }

    /** @test */
    public function changing_the_prefix_changes_the_prefix()
    {
        $function = (new EmptyTestFunction);

        $this->assertEquals(
            'sc-laravel-testing-7a7aecar-tests-unit-support-emptytestfunction',
            $function->nameWithPrefix()
        );

        config([
            'sidecar.lambda_prefix' => 'FOO',
        ]);

        $this->assertEquals(
            'foo-laravel-testing-7a7acar-tests-unit-support-emptytestfunction',
            $function->nameWithPrefix()
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
