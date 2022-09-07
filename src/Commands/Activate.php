<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Deployment;
use Hammerstone\Sidecar\Exceptions\NoFunctionsRegisteredException;
use Hammerstone\Sidecar\Sidecar;

class Activate extends EnvironmentAwareCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sidecar:activate {--pre-warm}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate Sidecar functions that have already been deployed.';

    /**
     * @throws NoFunctionsRegisteredException
     */
    public function handle()
    {
        $this->overrideEnvironment();
        Sidecar::addCommandLogger($this);

        Deployment::make()->activate($this->option('pre-warm'));
    }
}
