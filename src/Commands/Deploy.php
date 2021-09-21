<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Sidecar\Commands;

use Hammerstone\Sidecar\Deployment;
use Hammerstone\Sidecar\Exceptions\NoFunctionsRegisteredException;
use Hammerstone\Sidecar\Sidecar;

class Deploy extends EnvironmentAwareCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sidecar:deploy {--activate} {--pre-warm}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy Sidecar functions.';

    /**
     * @throws NoFunctionsRegisteredException
     * @throws Exception
     */
    public function handle()
    {
        $this->overrideEnvironment();
        Sidecar::addCommandLogger($this);

        $deployment = Deployment::make()->deploy();

        if ($this->option('activate')) {
            $deployment->activate($this->option('pre-warm'));
        }
    }
}
