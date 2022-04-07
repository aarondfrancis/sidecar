<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Vercel;

use GuzzleHttp\Client as Guzzle;
use Hammerstone\Sidecar\Contracts\FaasClient;
use Hammerstone\Sidecar\Exceptions\ConfigurationException;
use Hammerstone\Sidecar\ServerlessFunction;
use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Vercel\Concerns\Primitives;
use Illuminate\Support\Arr;

class Client implements FaasClient
{
    use Primitives;

    /**
     * @var Guzzle
     */
    protected $client;

    /**
     * @var string
     */
    protected $seed;

    /**
     * @var string
     */
    protected $secret;

    public function __construct($config)
    {
        $this->client = new Guzzle($config);
        $this->seed = config('sidecar.vercel_domain_seed');
        $this->secret = config('sidecar.vercel_signing_secret');
    }

    protected function validateConfiguration()
    {
        if (!$this->seed || !$this->secret) {
            throw new ConfigurationException('The Vercel seed and secret must be set.');
        }
    }

    public function getLatestVersion(ServerlessFunction $function)
    {
        $versions = $this->getVersions($function);

        return end($versions)['Version'];
    }

    public function aliasVersion(ServerlessFunction $function, $alias, $version = null)
    {
        // These don't ever get used by humans, so they
        // don't need to be decipherable.
        $domain = $this->domainForFunction($function, $alias);

        // @TODO This doesnt seem right
        $existing = $this->getDeploymentAliases($version);

        foreach ($existing['aliases'] as $assigned) {
            if ($assigned['alias'] === "$domain.vercel.app") {
                return FaasClient::NOOP;
            }
        }

        $this->setDeploymentAlias($version, $domain);

        return FaasClient::UPDATED;
    }

    public function updateFunctionVariables(ServerlessFunction $function)
    {
        // @TODO this is duped from Lambda client
        $variables = $function->variables();

        // Makes the checksum hash more stable.
        ksort($variables);

        // Add a checksum so that we can easily see later if anything has changed.
        $variables['SIDECAR_CHECKSUM'] = substr(md5(json_encode($variables)), 0, 8);

        // @TODO
        $projectId = 1;

        // @TODO see if anything has changed
        $this->setProjectEnv($projectId, $variables);
    }


    public function createFunction(array $args = [])
    {
        // @TODO args is function -> deployment config
//        $response = $this->createProject([
//            'name' => $function->nameWithPrefix(),
//        ]);

        // @TODO deploy the first time.
    }

    public function functionExists(ServerlessFunction $function, $checksum = null)
    {
        // @TODO checksum?
        return $this->projectExists($function->nameWithPrefix());
    }

    public function updateExistingFunction(ServerlessFunction $function)
    {
        if ($function->packageType() === 'Image') {
            throw new \Exception('Cannot deploy containers to Vercel. You must use a zip file.');
        }

        // @TODO see if anything has changed.

        $response = $this->createDeployment([
            // Project name?
            "name" => $function->nameWithPrefix(),
            // Arbitrary KV Pairs
            "meta" => (object) [
                //
            ],
            "projectSettings" => [
                "sourceFilesOutsideRootDirectory" => true
            ],
            "source" => "cli",
            "version" => 2,
            "functions" => [
                // Universal shim entrypoint from our scaffolding.
                "api/index.js" => [
                    "memory" => $function->memory(),
                    "maxDuration" => $function->timeout(),
                ]
            ],
            "routes" => [
                [
                    // Route everything to our entrypoint
                    "src" => "/(.*)",
                    "dest" => (new Scaffolding($function))->entry()
                ]
            ],
            "files" => $this->uploadPackage($function)->toArray()
        ]);

        while (true) {
            $state = $this->getDeployment($response['id'])['readyState'];

            if ($state === 'QUEUED') {
                Sidecar::log('Queued at Vercel...');
            } elseif ($state === 'BUILDING') {
                Sidecar::log('Building on Vercel...');
            } elseif ($state === 'READY') {
                Sidecar::log('Deployed to Vercel!');
                break;
            } else {
                throw new \Exception('Unknown Vercel state: ' . json_encode($state));
            }

            sleep(3);
        }
    }

    public function domainForFunction(ServerlessFunction $function, $alias)
    {
        return $this->domainForFunctionName($function->nameWithPrefix(), $alias);
    }

    public function domainForFunctionName($name, $alias)
    {
        $this->validateConfiguration();

        return strtolower(implode('-', [
            // Obscure the name, but make it still determinative.
            substr(md5($name), 0, 16),
            // Domain seed, can be public.
            $this->seed,
            $alias
        ]));
    }

    public function invoke($args = [])
    {
        return $this->doInvocation($args, $async = false);
    }

    public function invokeAsync($args = [])
    {
        return $this->doInvocation($args, $async = true);
    }

    public function executionUrl($function, $minutes)
    {
        $domain = $this->domainForFunction($function, 'active');
        $timestamp = now()->addMinutes($minutes)->timestamp;
        $digest = sha1("token{$timestamp}");

        return "https://$domain.vercel.app/tok-$digest-$timestamp/execute";
    }

    protected function doInvocation($args, $async)
    {
        $this->validateConfiguration();

        $function = explode(':', $args['FunctionName']);
        $domain = $this->domainForFunctionName($function[0], $function[1]);

        $url = "https://$domain.vercel.app/execute";

        $method = $async ? 'postAsync' : 'post';

        $response = (new Guzzle)->{$method}($url, [
            'body' => $args['Payload'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->secret
            ],
            'query' => [
                'scevent' => ($args['InvocationType'] === 'Event')
            ],
        ]);

        $response = $response->getBody()->getContents();
        $response = json_decode($response);

        dd($response);
    }

    public function getVersions(ServerlessFunction $function, $marker = null)
    {
        // @TODO Marker?

        $project = $this->getProject($function->nameWithPrefix());
        $response = $this->listDeployments($project['id']);

        $deployments = array_reverse($response['deployments']);

        return array_map(function ($deployment) {
            return [
                'Version' => $deployment['uid']
            ];
        }, $deployments);
    }

    public function deleteFunctionVersion(ServerlessFunction $function, $version)
    {
        $project = $this->getProject($function->nameWithPrefix());

        $keep = array_map(function ($config) {
            return $config['id'];
        }, $project['targets']);

        $keep = array_values($keep);

        if (in_array($version, $keep)) {
            Sidecar::log("Not deleting version $keep, as it is currently active.");
            return;
        }

        $this->client->delete("/v13/deployments/$version");
    }

    public function waitUntilFunctionUpdated(ServerlessFunction $function)
    {
        // There is no concept of waiting with Vercel.
        // Once it's deployed, it's live.
    }

    public function latestVersionHasAlias(ServerlessFunction $function, $alias)
    {
        // TODO: Implement latestVersionHasAlias() method.
    }

    public function uploadPackage(ServerlessFunction $function)
    {
        Sidecar::log('Uploading files to Vercel.');

        $files = (new Scaffolding($function))->files();

        // Put the developer's code in a zip and write it to a tmp file.
        $tmpzip = $function->makePackage()->createZip();

        $files->push([
            // Developer's code is zipped into this hardcoded path. It is
            // similarly hardcoded in the build step of package.json.
            'file' => '/package/package.zip',
            'stream' => fopen($tmpzip, 'r'),
            'sha' => sha1_file($tmpzip),
            'size' => filesize($tmpzip),
        ]);

        $files->each(function ($file) {
            $this->uploadFile($file);
        });

        @unlink($tmpzip);

        return $files->map(function ($file) {
            Arr::forget($file, 'stream');

            return $file;
        });
    }

}