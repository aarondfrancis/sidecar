<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Tests\Unit;

use Hammerstone\Sidecar\Architecture;
use Hammerstone\Sidecar\ArchitectureConstants;

class ArchitectureTest extends Base
{
    public function test_architecture_is_a_backed_enum()
    {
        $this->assertInstanceOf(\BackedEnum::class, Architecture::X86_64);
    }

    public function test_x86_64_has_correct_value()
    {
        $this->assertEquals('x86_64', Architecture::X86_64->value);
    }

    public function test_arm64_has_correct_value()
    {
        $this->assertEquals('arm64', Architecture::ARM_64->value);
    }

    public function test_architecture_can_be_created_from_string()
    {
        $this->assertEquals(Architecture::X86_64, Architecture::from('x86_64'));
        $this->assertEquals(Architecture::ARM_64, Architecture::from('arm64'));
    }

    public function test_architecture_constants_class_exists_for_backwards_compatibility()
    {
        $this->assertTrue(class_exists(ArchitectureConstants::class));
    }

    public function test_architecture_constants_match_enum_values()
    {
        $this->assertEquals(Architecture::X86_64->value, ArchitectureConstants::X86_64);
        $this->assertEquals(Architecture::ARM_64->value, ArchitectureConstants::ARM_64);
    }
}
