<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Concerns;

trait ManagesEnvironments
{
    protected ?string $environment = null;

    public function overrideEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    public function clearEnvironment(): void
    {
        $this->environment = null;
    }

    public function getEnvironment(): string
    {
        return $this->environment ?? config('sidecar.env') ?? config('app.env', 'production');
    }
}
