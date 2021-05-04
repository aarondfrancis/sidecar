<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Events;

class BeforeFunctionsActivated
{
    public $functions = [];

    /**
     * @param $functions
     */
    public function __construct($functions)
    {
        $this->functions = $functions;
    }
}
