<?php

declare(strict_types=1);

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

class WarmingConfig
{
    public int $instances = 0;

    public array $payload = [
        'warming' => true
    ];

    public function __construct(int $instances = 0)
    {
        $this->instances = $instances;
    }

    public static function instances($count)
    {
        $self = new static;

        $self->instances = $count;

        return $self;
    }

    public function withPayload($payload = [])
    {
        $this->payload = $payload;

        return $this;
    }
}
