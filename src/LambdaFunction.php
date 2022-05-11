<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Aws\Result;
use GuzzleHttp\Promise\PromiseInterface;
use Hammerstone\Sidecar\Exceptions\SidecarException;
use Hammerstone\Sidecar\Results\PendingResult;
use Hammerstone\Sidecar\Results\SettledResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class LambdaFunction
{
    /**
     * Execute the current function and return the response.
     *
     * @param  array  $payload
     * @param  bool  $async
     * @return SettledResult|PendingResult
     */
    public static function execute($payload = [], $async = false, $invocationType = 'RequestResponse')
    {
        return Sidecar::execute(static::class, $payload, $async, $invocationType);
    }

    /**
     * Execute the current function and return the response.
     *
     * @param  array  $payload
     * @return PendingResult
     */
    public static function executeAsync($payload = [])
    {
        return static::execute($payload, $async = true);
    }

    /**
     * Execute the current function and return the response.
     *
     * @param $payloads
     * @param  bool  $async
     * @return array
     *
     * @throws \Throwable
     */
    public static function executeMany($payloads, $async = false)
    {
        return Sidecar::executeMany(static::class, $payloads, $async);
    }

    /**
     * Execute the current function and return the response.
     *
     * @param $payloads
     * @return array
     *
     * @throws \Throwable
     */
    public static function executeManyAsync($payloads)
    {
        return static::executeMany($payloads, $async = true);
    }

    /**
     * Execute the current function asynchronously as an event. This is "fire-and-forget" style.
     *
     * @param  array  $payload
     * @return PendingResult
     */
    public static function executeAsEvent($payload = [])
    {
        return static::execute($payload, $async = false, $invocationType = 'Event');
    }

    /**
     * Deploy this function only.
     *
     * @param  bool  $activate
     */
    public static function deploy($activate = true)
    {
        $deployment = Deployment::make(static::class)->deploy();

        if ($activate) {
            $deployment->activate();
        }
    }

    /**
     * Used by Lambda to uniquely identify a function.
     *
     * @return string
     */
    public function name()
    {
        return Str::replaceFirst('App-', '', str_replace('\\', '-', static::class));
    }

    /**
     * Used by Sidecar to differentiate between apps and environments.
     *
     * @return string
     */
    public function prefix()
    {
        return 'SC-' . config('app.name') . '-' . Sidecar::getEnvironment() . '-';
    }

    /**
     * Function name, including a prefix to differentiate between apps.
     *
     * @return string
     */
    public function nameWithPrefix()
    {
        $prefix = $this->prefix();

        // Names can only be 64 characters long.
        $name = $prefix . substr($this->name(), -(64 - strlen($prefix)));

        return str_replace(' ', '-', $name);
    }

    /**
     * Not used by Sidecar at all, use as you see fit.
     *
     * @return string
     */
    public function description()
    {
        return sprintf('%s [%s]: Sidecar function `%s`.', ...[
            config('app.name'),
            config('app.env'),
            static::class,
        ]);
    }

    /**
     * A warming configuration that can help mitigate against
     * the Lambda "Cold Boot" problem.
     *
     * @return WarmingConfig
     */
    public function warmingConfig()
    {
        return new WarmingConfig;
    }

    /**
     * The default representation of this function as an HTTP response.
     *
     * @param $request
     * @param  SettledResult  $result
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function toResponse($request, SettledResult $result)
    {
        $result->throw();

        return response($result->body());
    }

    /**
     * @param  Result|PromiseInterface  $raw
     * @return SettledResult|PendingResult
     *
     * @throws SidecarException
     */
    public function toResult($raw)
    {
        if ($raw instanceof Result) {
            return $this->toSettledResult($raw);
        }

        if ($raw instanceof PromiseInterface) {
            return $this->toPendingResult($raw);
        }

        throw new SidecarException('Unable to determine Result for class ' . json_encode(get_class($raw)));
    }

    /**
     * @param  Result  $raw
     * @return SettledResult
     */
    public function toSettledResult(Result $raw)
    {
        return new SettledResult($raw, $this);
    }

    /**
     * @param  PromiseInterface  $raw
     * @return PendingResult
     */
    public function toPendingResult(PromiseInterface $raw)
    {
        return new PendingResult($raw, $this);
    }

    /**
     * The runtime environment for the Lambda function.
     *
     * @see https://docs.aws.amazon.com/lambda/latest/dg/lambda-runtimes.html
     *
     * @return string
     */
    public function runtime()
    {
        return Runtime::NODEJS_14;
    }

    /**
     * The architecture for the Lambda function.
     *
     * @return string
     */
    public function architecture()
    {
        return config('sidecar.architecture', Architecture::X86_64);
    }

    /**
     * The type of deployment package. Set to Image for container
     * image and set Zip for .zip file archive.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-lambda-2015-03-31.html#createfunction
     *
     * @return string
     */
    public function packageType()
    {
        return $this->handler() === Package::CONTAINER_HANDLER ? 'Image' : 'Zip';
    }

    /**
     * An array full of ARN strings. Totally optional.
     *
     * @return array
     */
    public function layers()
    {
        return [
            // 'arn:aws:lambda:us-east-1:XXX:layer:XXX:1',
        ];
    }

    /**
     * A key/value array of environment variables that will be injected into the
     * environment of the Lambda function. If Sidecar manages your environment
     * variables, it will overwrite all variables that you set through the
     * AWS UI. Return false to disable.
     *
     * @return bool|array
     */
    public function variables()
    {
        // By default, Sidecar does not manage your environment variables.
        return false;
    }

    /**
     * The function within your code that Lambda calls to begin execution.
     * For Node.js, it is the `module-name.export` value in your function.
     *
     * For example, if your file is named "image.js" and in that file you have
     * an "exports.generate" function, your handler would be "image.generate".
     *
     * If your file lived in a folder called "lambda", you can just prepend the
     * path to your handler, leaving you with e.g. "lambda/image.generate".
     *
     * @see https://hammerstone.dev/sidecar/docs/main/functions/handlers-and-packages
     * @see https://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Lambda.LambdaClient.html#_createFunction
     *
     * @return string
     */
    abstract public function handler();

    /**
     * All the directories and files needed to run your function.
     *
     * @return Package
     */
    abstract public function package();

    /**
     * The amount of memory, in MB, your Lambda function is given. Lambda uses this
     * memory size to infer the amount of CPU and memory allocated to your function.
     * Your function use-case determines your CPU and memory requirements.
     *
     * @return int
     */
    public function memory()
    {
        return config('sidecar.memory');
    }

    /**
     * The function execution time, in MS, at which Lambda should terminate the function.
     * Because the execution time has cost implications, we recommend you set this
     * value based on your expected execution time.
     *
     * @return int
     */
    public function timeout()
    {
        return config('sidecar.timeout');
    }

    /**
     * The ephemeral storage, in MB, your Lambda function is given.
     *
     * @return int
     */
    public function storage()
    {
        return config('sidecar.storage');
    }

    public function preparePayload($payload)
    {
        return $payload;
    }

    public function beforeDeployment()
    {
        //
    }

    public function afterDeployment()
    {
        //
    }

    public function beforeActivation()
    {
        //
    }

    public function afterActivation()
    {
        //
    }

    public function beforeExecution($payload)
    {
        //
    }

    public function afterExecution($payload, $result)
    {
        //
    }

    /**
     * @return array
     */
    public function normalizedHandler()
    {
        $handler = $this->handler();

        // Allow an at-sign to separate the file and function. This
        // matches the Laravel ecosystem better: `image@handler`
        // and `image.handler` will work the exact same way.
        if (is_string($handler) && Str::contains($handler, '@')) {
            $handler = Str::replace('@', '.', $handler);
        }

        return $handler;
    }

    /**
     * @return Package
     */
    public function makePackage()
    {
        $package = $this->package();

        return is_array($package) ? Package::make($package) : $package;
    }

    /**
     * @return array
     *
     * @throws SidecarException
     */
    public function toDeploymentArray()
    {
        $config = [
            'FunctionName' => $this->nameWithPrefix(),
            'Runtime' => $this->runtime(),
            'Role' => config('sidecar.execution_role'),
            'Handler' => $this->normalizedHandler(),
            'Code' => $this->packageType() === 'Zip'
                ? $this->makePackage()->deploymentConfiguration()
                : $this->package(),
            'Description' => $this->description(),
            'Timeout' => (int)$this->timeout(),
            'MemorySize' => (int)$this->memory(),
            'EphemeralStorage' => [
                'Size' => (int)$this->storage(),
            ],
            'Layers' => $this->layers(),
            'Publish' => true,
            'PackageType' => $this->packageType(),
            'Architectures' => [$this->architecture()]
        ];

        // For container image packages, we need to remove the Runtime
        // and Handler since the container handles both of those
        // things inherently.
        if ($this->packageType() === 'Image') {
            $config = Arr::except($config, ['Runtime', 'Handler']);
        }

        return $config;
    }
}
