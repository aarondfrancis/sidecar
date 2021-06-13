<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Exceptions;

use Throwable;

class NoFunctionsRegisteredException extends SidecarException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = "No Sidecar functions have been configured. \n" .
            "Please check your config/sidecar.php file to ensure you have registered your functions. \n" .
            'Read more at https://hammerstone.dev/sidecar/docs/main/configuration#registering-functions';

        parent::__construct($message, $code, $previous);
    }
}
