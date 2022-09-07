<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Sidecar;
use Illuminate\Console\Command;

class Warm extends EnvironmentAwareCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sidecar:warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send warming requests to Sidecar functions.';

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->overrideEnvironment();
        Sidecar::addCommandLogger($this);

        Sidecar::warm();
    }
}
