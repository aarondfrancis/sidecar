<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

use Aws\Result;
use GuzzleHttp\Promise\PromiseInterface;
use Hammerstone\Sidecar\Exceptions\SidecarException;
use Hammerstone\Sidecar\Results\SettledResult;
use Hammerstone\Sidecar\Results\PendingResult;
use Illuminate\Support\Str;

abstract class LambdaFunction
{
    /**
     * Execute the current function and return the response.
     *
     * @param array $payload
     * @param bool $async
     * @return SettledResult|PendingResult
     */
    public static function execute($payload = [], $async = false)
    {
        return Sidecar::execute(static::class, $payload, $async);
    }

    /**
     * Execute the current function and return the response.
     *
     * @param array $payload
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
     * @param bool $wait
     * @return array
     * @throws \Throwable
     */
    public static function executeMany($payloads, $wait = true)
    {
        if (is_int($payloads)) {
            $params = array_fill(0, $payloads, static::class);
        } else {
            $params = array_map(function ($payload) {
                return [
                    'function' => static::class,
                    'payload' => $payload
                ];
            }, $payloads);
        }

        return Sidecar::executeMany($params, $wait);
    }

    /**
     * Deploy this function only.
     */
    public static function deploy()
    {
        return Sidecar::deploy(static::class);
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
     * Function name, including a prefix to differentiate between apps.
     *
     * @return string
     */
    public function nameWithPrefix()
    {
        // Differentiate between apps and environments.
        $prefix = 'SC-' . config('app.name') . '-' . config('app.env') . '-';

        // Names can only be 64 characters long.
        return $prefix . substr($this->name(), -(64 - strlen($prefix)));
    }

    /**
     * Not used by Lambda at all, use as you see fit.
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
     * The default representation of this function as an HTTP response.
     *
     * @param $request
     * @param SettledResult $result
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function toResponse($request, SettledResult $result)
    {
        $result->throw();

        return response($result->body());
    }

    /**
     * @param Result|PromiseInterface $raw
     * @return SettledResult|PendingResult
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
     * @param Result $raw
     * @return SettledResult
     */
    public function toSettledResult(Result $raw)
    {
        return new SettledResult($raw, $this);
    }

    /**
     * @param PromiseInterface $raw
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
     * @return string
     */
    public function runtime()
    {
        return 'nodejs14.x';
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
     * The function within your code that Lambda calls to begin execution.
     * For Node.js, it is the `module-name.export` value in your function.
     *
     * For example, if your file is named "image.js" and in that file you have
     * an "exports.generate" function, your handler would be "image.generate".
     *
     * If your file lived in a folder called "lambda", you can just prepend the
     * path to your handler, leaving you with e.g. "lambda/image.generate".
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Lambda.LambdaClient.html#_createFunction
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
     * The value must be a multiple of 64 MB.
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

    public function beforeDeployment()
    {
        //
    }

    public function afterDeployment()
    {
        //
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
     * @throws SidecarException
     */
    public function toDeploymentArray()
    {
        return [
            'FunctionName' => $this->nameWithPrefix(),
            'Runtime' => $this->runtime(),
            'Role' => config('sidecar.role_arn'),
            'Handler' => $this->handler(),
            'Code' => $this->makePackage()->deploymentConfiguration(),
            'Description' => $this->description(),
            'Timeout' => $this->timeout(),
            'MemorySize' => 64 * round($this->memory() / 64),
            'Layers' => $this->layers(),
            'Publish' => true,
        ];
    }

}
