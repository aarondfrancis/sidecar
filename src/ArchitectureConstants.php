<?php

declare(strict_types=1);

namespace Hammerstone\Sidecar;

/**
 * @deprecated Use the Architecture enum instead. This class will be removed in a future version.
 */
abstract class ArchitectureConstants
{
    /** @deprecated Use Architecture::X86_64 instead */
    public const X86_64 = 'x86_64';

    /** @deprecated Use Architecture::ARM_64 instead */
    public const ARM_64 = 'arm64';
}
