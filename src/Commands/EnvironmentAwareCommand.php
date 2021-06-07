<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Sidecar;
use Illuminate\Console\Command;
use Illuminate\Console\Parser;

abstract class EnvironmentAwareCommand extends Command
{
    public function __construct()
    {
        parent::__construct();

        $environment = '{--env=}';

        // https://twitter.com/marcelpociot/status/1395026412319526912/photo/1
        $this->getDefinition()->addOptions(Parser::parse($environment)[2]);
    }

    public function overrideEnvironment()
    {
        if ($environment = $this->option('env')) {
            Sidecar::overrideEnvironment($environment);
        }
    }
}
