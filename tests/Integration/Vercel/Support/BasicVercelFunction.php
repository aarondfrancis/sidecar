<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Integration\Vercel\Support;

use Hammerstone\Sidecar\Package;
use Hammerstone\Sidecar\ServerlessFunction;

class BasicVercelFunction extends ServerlessFunction
{
    public function name()
    {
        return 'Vercel-Test';
    }

    public function handler()
    {
        return 'helloworld.handler';
    }

    public function package()
    {
        return Package::make()
            ->setBasePath(__DIR__ . DIRECTORY_SEPARATOR . 'Files')
            ->include([
                'helloworld.js'
            ]);
    }
}
