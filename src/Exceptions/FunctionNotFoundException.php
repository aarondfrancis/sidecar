<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Exceptions;

use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Sidecar;

class FunctionNotFoundException extends SidecarException
{
    public static function make(LambdaFunction $function)
    {
        $env = Sidecar::getEnvironment();

        $message = <<<EOT
Function `{$function->name()}` not found in environment `{$env}`.
It may exist other environments, you may need to overwrite the environment while deploying to `{$env}`.
See https://hammerstone.dev/sidecar/docs/main/functions/deploying#faking-the-environment for more information.
EOT;

        return new static($message);
    }
}
