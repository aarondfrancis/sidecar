<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Clients;

use Aws\Lambda\Exception\LambdaException;
use Aws\Lambda\LambdaClient as BaseClient;
use Aws\Result;
use Exception;
use Hammerstone\Sidecar\Contracts\FaasClient;
use Hammerstone\Sidecar\ServerlessFunction;
use Hammerstone\Sidecar\Sidecar;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LambdaClient extends BaseClient implements FaasClient
{
    /**
     * @param  ServerlessFunction  $function
     * @return string
     */
    public function getLatestVersion(ServerlessFunction $function)
    {
        return last($this->getVersions($function))['Version'];
    }

    /**
     * Test whether the latest deployed version is the one that is aliased.
     *
     * @param  ServerlessFunction  $function
     * @param $alias
     * @return bool
     */
    public function latestVersionHasAlias(ServerlessFunction $function, $alias)
    {
        $version = $this->getLatestVersion($function);

        $aliased = $this->getAliasWithoutException($function, $alias);

        return $aliased && $version === Arr::get($aliased, 'FunctionVersion');
    }

    /**
     * @param  ServerlessFunction  $function
     * @param  null|string  $marker
     * @return \Aws\Result
     */
    public function getVersions(ServerlessFunction $function, $marker = null)
    {
        $result = $this->listVersionsByFunction([
            'FunctionName' => $function->nameWithPrefix(),
            'MaxItems' => 50,
            'Marker' => $marker,
        ]);

        $versions = $result['Versions'];

        if ($marker = Arr::get($result, 'NextMarker')) {
            $versions = array_merge($versions, $this->getVersions($function, $marker));
        }

        return $versions;
    }

    /**
     * @param  ServerlessFunction  $function
     * @param  string  $alias
     * @param  string|null  $version
     * @return int
     */
    public function aliasVersion(ServerlessFunction $function, $alias, $version = null)
    {
        $version = $version ?? $this->getLatestVersion($function);

        $aliased = $this->getAliasWithoutException($function, $alias);

        // The alias already exists and it's the version we were trying to alias anyway.
        if ($aliased && $version === Arr::get($aliased, 'FunctionVersion')) {
            return FaasClient::NOOP;
        }

        $args = [
            'FunctionName' => $function->nameWithPrefix(),
            'Name' => $alias,
            'FunctionVersion' => $version,
        ];

        if ($aliased) {
            $this->updateAlias($args);

            return FaasClient::UPDATED;
        }

        $this->createAlias($args);

        return FaasClient::CREATED;
    }

    /**
     * @param  ServerlessFunction  $function
     * @param $name
     * @return \Aws\Result|false
     */
    public function getAliasWithoutException(ServerlessFunction $function, $name)
    {
        try {
            return $this->getAlias([
                'FunctionName' => $function->nameWithPrefix(),
                'Name' => $name,
            ]);
        } catch (LambdaException $e) {
            if ($e->getStatusCode() !== 404) {
                throw $e;
            }
        }

        return false;
    }

    /**
     * @param  ServerlessFunction  $function
     * @throws \Hammerstone\Sidecar\Exceptions\SidecarException
     */
    public function createNewFunction(ServerlessFunction $function)
    {
        $this->createFunction($function->toDeploymentArray());
    }

    /**
     * @param  ServerlessFunction  $function
     * @return int
     *
     * @throws Exception
     */
    public function updateExistingFunction(ServerlessFunction $function)
    {
        $config = $function->toDeploymentArray();

        // Since the code package has a unique name, this checksum
        // encompasses both the code and the configuration.
        $checksum = substr(md5(json_encode($config)), 0, 8);

        // See if the function already exists with these *exact* parameters.
        if ($this->functionExists($function, $checksum)) {
            return FaasClient::NOOP;
        }

        // Add the checksum to the description, so we can look for it next time.
        $config['Description'] .= " [$checksum]";

        // For the updateFunctionCode call, AWS requires that the S3Bucket
        // and S3Key be top level instead of nested under `Code`.
        $code = [
            'FunctionName' => $config['FunctionName'],
            'Publish' => $config['Publish']
        ];

        if ($function->packageType() === 'Zip') {
            $code['S3Bucket'] = $config['Code']['S3Bucket'];
            $code['S3Key'] = $config['Code']['S3Key'];
        } else {
            $code = array_merge($code, $function->package());
        }

        $config = Arr::except($config, ['Code', 'Publish']);

        $this->waitUntilFunctionUpdated($function);
        $this->updateFunctionConfiguration($config);

        $this->waitUntilFunctionUpdated($function);
        $this->updateFunctionCode($code);
    }

    public function updateFunctionVariables(ServerlessFunction $function)
    {
        $variables = $function->variables();

        // Makes the checksum hash more stable.
        ksort($variables);

        // Add a checksum so that we can easily see later if anything has changed.
        $variables['SIDECAR_CHECKSUM'] = substr(md5(json_encode($variables)), 0, 8);

        /** @var Result $response */
        $response = $this->getFunctionConfiguration([
            'FunctionName' => $function->nameWithPrefix(),
        ]);

        // If the variables haven't changed at all, don't waste
        // time setting them and activating a new version.
        if ($variables['SIDECAR_CHECKSUM'] === Arr::get($response, 'Environment.Variables.SIDECAR_CHECKSUM')) {
            Sidecar::log('Environment variables unchanged.');

            return;
        }

        Sidecar::log('Updating environment variables.');

        $this->waitUntilFunctionUpdated($function);

        $this->updateFunctionConfiguration([
            'FunctionName' => $function->nameWithPrefix(),
            'Environment' => [
                'Variables' => $variables,
            ],
        ]);

        Sidecar::log('Publishing new version with new environment variables.');

        $this->waitUntilFunctionUpdated($function);

        $this->publishVersion([
            'FunctionName' => $function->nameWithPrefix(),
        ]);
    }

    /**
     * Wait until the function is out of the Pending state, so that
     * we don't get 409 conflict errors.
     *
     * @link https://aws.amazon.com/de/blogs/compute/coming-soon-expansion-of-aws-lambda-states-to-all-functions/
     * @link https://aws.amazon.com/blogs/compute/tracking-the-state-of-lambda-functions/
     * @link https://github.com/hammerstonedev/sidecar/issues/32
     * @link https://github.com/aws/aws-sdk-php/blob/master/src/data/lambda/2015-03-31/waiters-2.json
     *
     * @param  ServerlessFunction  $function
     */
    public function waitUntilFunctionUpdated(ServerlessFunction $function)
    {
        $this->waitUntil('FunctionUpdated', [
            'FunctionName' => $function->nameWithPrefix(),
        ]);
    }

    /**
     * Delete a particular version of a function.
     *
     * @param  ServerlessFunction  $function
     * @param  string  $version
     */
    public function deleteFunctionVersion(ServerlessFunction $function, $version)
    {
        $this->deleteFunction([
            'FunctionName' => $function->nameWithPrefix(),
            'Qualifier' => $version
        ]);
    }

    /**
     * @param  ServerlessFunction  $function
     * @param  null  $checksum
     * @return bool
     */
    public function functionExists(ServerlessFunction $function, $checksum = null)
    {
        try {
            $response = $this->getFunction([
                'FunctionName' => $function->nameWithPrefix(),
            ]);
        } catch (LambdaException $e) {
            // If it's a 404, then that means the function doesn't
            // exist, which is what we're trying to figure out.
            if ($e->getStatusCode() === 404) {
                return false;
            }

            // If it's some other kind of error, we need to bail.
            throw $e;
        }

        // If the checksum is null, then we're checking to see if any
        // version of this function exists, not necessarily a
        // particular configuration of the function.
        if (is_null($checksum)) {
            return true;
        }

        // See if the description contains the checksum.
        return Str::contains(Arr::get($response, 'Configuration.Description', ''), $checksum);
    }
}
