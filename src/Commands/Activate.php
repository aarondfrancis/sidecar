<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Deployment;
use Hammerstone\Sidecar\Sidecar;
use Illuminate\Console\Command;

class Activate extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sidecar:activate';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Activate Sidecar functions that have already been deployed.';

    /**
     * @throws Exception
     */
    public function handle()
    {
        Sidecar::addLogger(function ($message) {
            $this->info($message);
        });

        Deployment::make()->activate();
    }

}
