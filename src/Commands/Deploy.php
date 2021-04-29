<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Sidecar;
use Illuminate\Console\Command;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sidecar:deploy';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Deploy Sidecar functions.';

    /**
     * @throws Exception
     */
    public function handle()
    {
        Sidecar::addLogger(function ($message) {
            $this->info($message);
        });

        Sidecar::deploy();
    }

}
