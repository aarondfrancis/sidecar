<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Hammerstone\Sidecar\Commands\Configure;

abstract class BaseAction
{
    public function __construct(
        public ?string $region,
        public Configure $command
    ) {}

    abstract public function invoke(): mixed;

    protected function progress(string $message): void
    {
        $this->command->text("==> $message");
    }
}
