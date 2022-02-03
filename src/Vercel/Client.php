<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Vercel;

use App\Sidecar\HelloFunction;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Hammerstone\Sidecar\Clients\LambdaClient;
use Hammerstone\Sidecar\Finder;
use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Sidecar;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Client
{
    protected function client()
    {
        return new \GuzzleHttp\Client([
            'base_uri' => 'https://api.vercel.com/',
            'allow_redirects' => true,
            'headers' => [
                'Authorization' => 'Bearer iJ8wSIHFXgTFOWjC24u1r0T8',
            ]
        ]);
    }

    protected function get($uri, array $options = [])
    {
        $response = $this->client()->get($uri, $options);

        return json_decode($response->getBody()->getContents(), JSON_OBJECT_AS_ARRAY);
    }

    protected function post($uri, array $options = [])
    {
        $response = $this->client()->post($uri, $options);

        return json_decode($response->getBody()->getContents(), JSON_OBJECT_AS_ARRAY);
    }

    public function listDeployments()
    {
        return $this->get('/v6/deployments');
    }

    public function getDeployment($id)
    {
        return $this->get("/v13/deployments/{$id}");
    }

    public function getDeploymentBuilds($id)
    {
        return $this->get("/v11/deployments/{$id}/builds");
    }

    public function getDeploymentAliases($id)
    {
        return $this->get("/v2/deployments/{$id}/aliases");
    }

    public function getLatestVersion(LambdaFunction $function)
    {
        $versions = $this->getVersions($function);

        return end($versions)['Version'];
    }

    public function aliasVersion(LambdaFunction $function, $alias, $version)
    {
        $seed = config('sidecar.vercel_seed');

        if (!$seed) {
            throw new \Exception('Vercel see is not set, unable to create domain.');
        }

        // These don't ever get used by humans, so they
        // don't need to be decipherable.
        $domain = implode('-', [
            substr(md5($function->nameWithPrefix()), 0, 16), $seed, $alias
        ]);

        $existing = $this->getDeploymentAliases($version);

        foreach ($existing['aliases'] as $assigned) {
            if ($assigned['alias'] === "$domain.vercel.app") {
                return LambdaClient::NOOP;
            }
        }

        $this->setDeploymentAlias($version, $domain);

        return LambdaClient::UPDATED;
    }

    public function setDeploymentAlias($id, $alias)
    {
        return $this->post("/v2/deployments/{$id}/aliases", [
            'json' => [
                'alias' => $alias,
            ],
        ]);
    }

    public function setProjectEnv($id, $env)
    {
        $env = <<<JSON
{
    "type": "encrypted",
    "key": "buzzz",
    "value": "bez",
    "target": [
        "production",
        "preview",
        "development"
    ]
}
JSON;

        return $this->post("/v7/projects/$id/env", [
            'json' => $env,
        ]);
    }

    public function createDeployment($json)
    {
        return $this->post('/v13/deployments', [
            'json' => $json,
        ]);
    }

    public function createProject($json)
    {
        return $this->post('/v8/projects', [
            'json' => $json,
        ]);
    }

    public function projectExists($idOrName)
    {
        try {
            $this->getProject($idOrName);
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    public function getProject($idOrName)
    {
        return $this->get("/v8/projects/{$idOrName}");
    }

    public function createFunction(LambdaFunction $function)
    {
        $response = $this->createProject([
            'name' => $function->nameWithPrefix(),
        ]);

        // @TODO deploy the first time.
    }

    public function uploadFile($file)
    {
        return $this->post('/v2/files', [
            'body' => $file['stream'],
            'headers' => [
                'Content-Length' => $file['size'],
                'x-now-size' => $file['size'],
                'x-now-digest' => $file['sha'],
            ]
        ]);
    }

    public function functionExists(LambdaFunction $function)
    {
        return $this->projectExists($function->nameWithPrefix());
    }

    public function updateExistingFunction(LambdaFunction $function)
    {
        if ($function->packageType() === 'Image') {
            throw new \Exception('Cannot deploy containers to Vercel. You must use a zip file');
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
                // Universal shim entrypoint
                "api/index.js" => [
                    "memory" => 1024,
                    "maxDuration" => 5
                ]
            ],
            "routes" => [
                [
                    // Route everything to our entrypoint
                    "src" => "/(.*)",
                    "dest" => "/api/index.js"
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
                Sidecar::log('Building on Vercel complete.');
                break;
            } else {
                throw new \Exception('Unknown Vercel state: ' . json_encode($state));
            }

            sleep(3);
        }
    }

    public function invoke($args)
    {
        return $this->doInvocation($args, $async = false);
    }

    public function invokeAsync($args)
    {
        return $this->doInvocation($args, $async = true);
    }

    public function executionUrl($function, $minutes)
    {
        $domain = implode('-', [
            substr(md5(app($function)->nameWithPrefix()), 0, 16), config('sidecar.vercel_seed'), 'active'
        ]);

        $timestamp = now()->addMinutes($minutes)->timestamp;
        $digest = sha1("token{$timestamp}");

        dd("https://$domain.vercel.app/tok-$digest-$timestamp/execute");
    }

    protected function doInvocation($args, $async)
    {
        $function = explode(':', $args['FunctionName']);

        $domain = implode('-', [
            substr(md5($function[0]), 0, 16), config('sidecar.vercel_seed'), $function[1]
        ]);

        $url = "https://$domain.vercel.app/execute";

        $method = $async ? 'postAsync' : 'post';

        $response = (new \GuzzleHttp\Client)->{$method}($url, [
            'body' => $args['Payload'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token'
            ],
            'query' => [
                'scevent' => ($args['InvocationType'] === 'Event')
            ],
        ]);

        $response = $response->getBody()->getContents();
        $response = json_decode($response);

        dd($response);
    }

    public function replacements(LambdaFunction $function)
    {
        return [
            'sc_replace__handler_file' => explode('.', $function->normalizedHandler())[0],
            'sc_replace__handler_function' => explode('.', $function->normalizedHandler())[1],
            // @TODO
            'sc_replace__middleware_token' => 'token'
        ];
    }

    public function getDeployments($projectId)
    {
        return $this->get('/v6/deployments', [
            'query' => [
                'projectId' => $projectId,
                'limit' => 100,
            ]
        ]);
    }

    public function getVersions(LambdaFunction $function)
    {
        $project = $this->getProject($function->nameWithPrefix());
        $response = $this->getDeployments($project['id']);

        $deployments = array_reverse($response['deployments']);

        return array_map(function ($deployment) {
            return [
                'Version' => $deployment['uid']
            ];
        }, $deployments);
    }

    public function deleteFunctionVersion(LambdaFunction $function, $version)
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

        $this->client()->delete("/v13/deployments/$version");
    }

    public function uploadPackage(LambdaFunction $function)
    {
        Sidecar::log('Uploading files to Vercel.');
        $scaffolding = __DIR__ . DIRECTORY_SEPARATOR . 'Scaffolding';

        // Grab everything in the scaffolding directory, exactly as is.
        $files = Finder::create($scaffolding)
            ->selected()
            ->map(function ($file) use ($function, $scaffolding) {
                $contents = str_replace(
                    array_keys($this->replacements($function)),
                    array_values($this->replacements($function)),
                    file_get_contents($file)
                );

                // Remove the base path.
                $name = Str::replace($scaffolding, '', $file);

                return [
                    'file' => $name,
                    'stream' => $contents,
                    'size' => strlen($contents),
                    'sha' => sha1($contents),
                ];
            });

        // Put the user's code in a zip and write it to a tmp file.
        $tmp = $function->makePackage()->createZip();

        $files->push([
            'file' => '/package/package.zip',
            'stream' => fopen($tmp, 'r'),
            'sha' => sha1_file($tmp),
            'size' => filesize($tmp),
        ]);

        $files->each(function ($file) {
            $this->uploadFile($file);
        });

        @unlink($tmp);

        return $files->map(function ($file) {
            Arr::forget($file, 'stream');

            return $file;
        });
    }
}