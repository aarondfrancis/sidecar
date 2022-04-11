<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Vercel\Concerns;

use GuzzleHttp\Exception\RequestException;

trait Primitives
{
    protected function get($uri, array $options = [])
    {
        return $this->request('get', $uri, $options);
    }

    protected function post($uri, array $options = [])
    {
        return $this->request('post', $uri, $options);
    }

    protected function request($method, $uri, array $options = [])
    {
        // https://vercel.com/docs/rest-api#introduction/api-basics/authentication/accessing-resources-owned-by-a-team
        if ($this->teamId) {
            $options = array_merge_recursive($options, [
                'query' => [
                    'teamId' => $this->teamId
                ]
            ]);
        }

        $response = $this->client->{$method}($uri, $options);

        return json_decode($response->getBody()->getContents(), JSON_OBJECT_AS_ARRAY);
    }

    /*
    |--------------------------------------------------------------------------
    | Teams
    |--------------------------------------------------------------------------
    */
    public function listTeams()
    {
        return $this->get('/v2/teams');
    }

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */
    public function getProject($idOrName)
    {
        return $this->get("/v8/projects/$idOrName");
    }

    public function createProject($json)
    {
        return $this->post('/v8/projects', [
            'json' => $json,
        ]);
    }

    public function deleteProject($idOrName)
    {
        return $this->request('DELETE', "/v8/projects/$idOrName");
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

    public function setProjectEnv($id, $variables)
    {
        $mapped = [];

        foreach ($variables as $key => $value) {
            $mapped[] = [
                'type' => 'encrypted',
                'key' => $key,
                'value' => $value,
                'target' => [
                    'production',
                    'preview',
                    'development'
                ]
            ];
        }

        return $this->post("/v7/projects/$id/env", [
            'json' => $mapped,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Files
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Deployments
    |--------------------------------------------------------------------------
    */
    public function getDeployment($id)
    {
        return $this->get("/v13/deployments/$id");
    }

    public function createDeployment($json)
    {
        return $this->post('/v13/deployments', [
            'json' => $json,
        ]);
    }

    public function listDeployments($projectId = null)
    {
        return $this->get('/v6/deployments', [
            'query' => $projectId ? [
                'projectId' => $projectId,
                'limit' => 100,
            ] : [
                //
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Deployment Aliases
    |--------------------------------------------------------------------------
    */
    public function setDeploymentAlias($id, $alias)
    {
        return $this->post("/v2/deployments/$id/aliases", [
            'json' => [
                'alias' => $alias,
            ],
        ]);
    }

    public function getDeploymentAliases($id)
    {
        return $this->get("/v2/deployments/$id/aliases");
    }

    /*
    |--------------------------------------------------------------------------
    | Deployment Builds
    |--------------------------------------------------------------------------
    */
    public function getDeploymentBuilds($id)
    {
        return $this->get("/v11/deployments/$id/builds");
    }
}
