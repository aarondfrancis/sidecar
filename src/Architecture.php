<?php

declare(strict_types=1);

namespace Hammerstone\Sidecar;

enum Architecture: string
{
    case X86_64 = 'x86_64';
    case ARM_64 = 'arm64';
}
