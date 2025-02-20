<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Unit\Support;

class DeploymentTestFunctionWithTags extends DeploymentTestFunction
{
    public function tags()
    {
        return [
            'Project' => 'Super Secret Project'
        ];
    }
}
