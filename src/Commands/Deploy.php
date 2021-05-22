<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Deployment;
use Hammerstone\Sidecar\Sidecar;

class Deploy extends EnvironmentAwareCommand
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sidecar:deploy {--activate}';

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
        $this->overrideEnvironment();
        Sidecar::addCommandLogger($this);

        Deployment::make()->deploy($this->option('activate'));
    }
}
