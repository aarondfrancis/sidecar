<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Clients;

use Aws\Lambda\Exception\LambdaException;
use Aws\Lambda\LambdaClient as BaseClient;
use Aws\Middleware;
use Aws\Result;
use Exception;
use Hammerstone\Sidecar\LambdaFunction;
use Hammerstone\Sidecar\Sidecar;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LambdaClient extends BaseClient
{
    const CREATED = 1;
    const UPDATED = 2;
    const NOOP = 3;

    public function __construct(array $args)
    {
        parent::__construct($args);

        $this->addPendingRetryMiddleware();
    }

    /**
     * @param  LambdaFunction  $function
     * @return string
     */
    public function getLatestVersion(LambdaFunction $function)
    {
        return last($this->getVersions($function))['Version'];
    }

    /**
     * Test whether the latest deployed version is the one that is aliased.
     *
     * @param  LambdaFunction  $function
     * @param $alias
     * @return bool
     */
    public function latestVersionHasAlias(LambdaFunction $function, $alias)
    {
        $version = $this->getLatestVersion($function);

        $aliased = $this->getAliasWithoutException($function, $alias);

        return $aliased && $version === Arr::get($aliased, 'FunctionVersion');
    }

    /**
     * @param  LambdaFunction  $function
     * @param  null|string  $marker
     * @return \Aws\Result
     */
    public function getVersions(LambdaFunction $function, $marker = null)
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
     * @param  LambdaFunction  $function
     * @param  string  $alias
     * @param  string|null  $version
     * @return int
     */
    public function aliasVersion(LambdaFunction $function, $alias, $version = null)
    {
        $version = $version ?? $this->getLatestVersion($function);

        $aliased = $this->getAliasWithoutException($function, $alias);

        // The alias already exists and it's the version we were trying to alias anyway.
        if ($aliased && $version === Arr::get($aliased, 'FunctionVersion')) {
            return self::NOOP;
        }

        $args = [
            'FunctionName' => $function->nameWithPrefix(),
            'Name' => $alias,
            'FunctionVersion' => $version,
        ];

        if ($aliased) {
            $this->updateAlias($args);

            return self::UPDATED;
        }

        $this->createAlias($args);

        return self::CREATED;
    }

    /**
     * @param  LambdaFunction  $function
     * @param $name
     * @return \Aws\Result|false
     */
    public function getAliasWithoutException(LambdaFunction $function, $name)
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
     * @param  LambdaFunction  $function
     * @return int
     *
     * @throws Exception
     */
    public function updateExistingFunction(LambdaFunction $function)
    {
        $config = $function->toDeploymentArray();

        // Since the code package has a unique name, this checksum
        // encompasses both the code and the configuration.
        $checksum = substr(md5(json_encode($config)), 0, 8);

        // See if the function already exists with these *exact* parameters.
        if ($this->functionExists($function, $checksum)) {
            return self::NOOP;
        }

        // Add the checksum to the description, so we can look for it next time.
        $config['Description'] .= " [$checksum]";

        // For the updateFunctionCode call, AWS requires that the S3Bucket
        // and S3Key be top level instead of nested under `Code`.
        $code = [
            'FunctionName' => $config['FunctionName'],
            'Publish' => $config['Publish'],
            'Architectures' => $config['Architectures'],
        ];

        if ($function->packageType() === 'Zip') {
            $code['S3Bucket'] = $config['Code']['S3Bucket'];
            $code['S3Key'] = $config['Code']['S3Key'];
        } else {
            $code = array_merge($code, $function->package());
        }

        $config = Arr::except($config, ['Code', 'Publish']);

        $this->updateFunctionConfiguration($config);
        $this->updateFunctionCode($code);
    }

    public function updateFunctionVariables(LambdaFunction $function)
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

        $this->updateFunctionConfiguration([
            'FunctionName' => $function->nameWithPrefix(),
            'Environment' => [
                'Variables' => $variables,
            ],
        ]);

        Sidecar::log('Publishing new version with new environment variables.');

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
     * @param  LambdaFunction|string  $function
     */
    public function waitUntilFunctionUpdated($function)
    {
        if ($function instanceof LambdaFunction) {
            $function = $function->nameWithPrefix();
        }

        if (is_string($function)) {
            // Strip off any aliases.
            $function = Str::beforeLast($function, ':');
        }

        return $this->waitUntil('FunctionUpdated', [
            'FunctionName' => $function,
        ]);
    }

    /**
     * Add a middleware that will retry all requests provided the response
     * is a 409 Conflict. We have to do this because AWS puts a function
     * in a "Pending" state as they propagate the updates everywhere.
     *
     * @see LambdaClient::waitUntilFunctionUpdated()
     */
    protected function addPendingRetryMiddleware()
    {
        $middleware = Middleware::retry(function ($attempt, $command, $request, $result, $exception) {
            // If the request succeeded, the exception will be null.
            return $exception instanceof LambdaException
                && $exception->getStatusCode() === 409
                && Str::contains($exception->getAwsErrorMessage(), [
                    'The function is currently in the following state: Pending',
                    'is currently in the following state: \'Pending\'',
                    'An update is in progress for resource: '
                ])
                && $this->waitUntilFunctionUpdated($command['FunctionName']);
        });

        // Add the middleware to the stack in the "sign" section.
        $this->getHandlerList()->appendSign($middleware);
    }

    /**
     * Delete a particular version of a function.
     *
     * @param  LambdaFunction  $function
     * @param  string  $version
     */
    public function deleteFunctionVersion(LambdaFunction $function, $version)
    {
        $this->deleteFunction([
            'FunctionName' => $function->nameWithPrefix(),
            'Qualifier' => $version
        ]);
    }

    /**
     * @param  LambdaFunction  $function
     * @param  null  $checksum
     * @return bool
     */
    public function functionExists(LambdaFunction $function, $checksum = null)
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
