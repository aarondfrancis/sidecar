<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Concerns;

trait ManagesEnvironments
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @param  string  $environment
     */
    public function overrideEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Clear the environment override.
     */
    public function clearEnvironment()
    {
        $this->environment = null;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment ?? config('sidecar.env') ?? config('app.env');
    }
}
