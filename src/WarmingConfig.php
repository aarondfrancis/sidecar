<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

class WarmingConfig
{
    public $instances = 0;

    public $payload = [
        'warming' => true
    ];

    public function __construct($instances = 1)
    {
        $this->instances = $instances;
    }

    public static function instances($count = 1)
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
