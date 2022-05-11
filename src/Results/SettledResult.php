<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Results;

use Aws\Result;
use Carbon\Carbon;
use Exception;
use Hammerstone\Sidecar\Exceptions\LambdaExecutionException;
use Hammerstone\Sidecar\LambdaFunction;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class SettledResult implements Responsable, ResultContract
{
    /**
     * @var Result
     */
    protected $raw;

    /**
     * @var LambdaFunction
     */
    protected $function;

    /**
     * @var array
     */
    protected $report = [];

    /**
     * @var string
     */
    protected $requestId;

    /**
     * @var array
     */
    protected $logs = [];

    public function __construct($raw, LambdaFunction $function)
    {
        $this->raw = $raw;
        $this->function = $function;

        $this->logs = $this->parseLogs();
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->raw->get('FunctionError') !== '';
    }

    /**
     * @return Result
     */
    public function rawAwsResult()
    {
        return $this->raw;
    }

    /**
     * This is here as a little nicety for the developer, so that they
     * can call `settled` on either kind of result (PendingResult
     * or SettledResult) and get a SettledResult back.
     *
     * @return $this
     */
    public function settled()
    {
        return $this;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws Exception
     */
    public function toResponse($request)
    {
        return $this->function->toResponse($request, $this);
    }

    /**
     * Throw an exception if there was an error, otherwise do nothing.
     *
     * @throws Exception
     */
    public function throw()
    {
        if (!$this->isError()) {
            return $this;
        }

        throw new LambdaExecutionException(sprintf('Lambda Execution Exception for %s: "%s".', ...[
            get_class($this->function),
            $this->errorAsString()
        ]));
    }

    /**
     * The Payload of the result is always JSON encoded, even if it's not JSON.
     *
     * @param  int  $options
     * @return mixed
     */
    public function body($options = JSON_OBJECT_AS_ARRAY)
    {
        return json_decode((string)$this->raw->get('Payload'), $options);
    }

    /**
     * @return array
     */
    public function logs()
    {
        return $this->logs;
    }

    /**
     * An aggregated report of the stats.
     *
     * @return array
     */
    public function report()
    {
        return [
            'request' => $this->requestId(),
            'billed_duration' => $this->billedDurationMs(),
            'execution_duration' => $this->executionDurationMs(),
            'cold_boot_delay' => $this->coldBootDelayMs(),
            'total_duration' => $this->totalDurationMs(),
            'max_memory' => $this->maxMemoryUsedMb(),
            'memory' => $this->memorySizeMb(),
        ];
    }

    public function requestId()
    {
        return $this->requestId;
    }

    public function executionDurationMs()
    {
        return $this->unitlessNumberFromReport('Duration');
    }

    public function coldBootDelayMs()
    {
        return $this->unitlessNumberFromReport('Init Duration') ?? 0;
    }

    public function billedDurationMs()
    {
        return $this->unitlessNumberFromReport('Billed Duration');
    }

    public function totalDurationMs()
    {
        return $this->coldBootDelayMs() + $this->executionDurationMs();
    }

    public function maxMemoryUsedMb()
    {
        return $this->unitlessNumberFromReport('Max Memory Used');
    }

    public function memorySizeMb()
    {
        return $this->unitlessNumberFromReport('Memory Size');
    }

    public function trace()
    {
        if (!$this->isError()) {
            return [];
        }

        return Arr::get($this->body(), 'trace', []);
    }

    public function errorAsString()
    {
        if (!$this->isError()) {
            return '';
        }

        $message = Arr::get($this->body(), 'errorMessage', 'Unknown error.');

        // Only the first two backtraces (plus the error) for the string.
        $trace = array_slice($this->trace(), 0, 3);
        $trace = implode(' ', array_map('trim', $trace));

        if ($trace) {
            return "$message. [TRACE] $trace";
        }

        return $message;
    }

    protected function parseLogs()
    {
        $lines = base64_decode($this->raw->get('LogResult'));
        $lines = explode("\n", $lines);

        $lines = array_map(function ($line) use (&$reportLineReached) {
            try {
                if ($reportLineReached) {
                    return null;
                }

                if (Str::startsWith($line, 'START RequestId:')) {
                    return $this->parseStartLine($line);
                }

                if (Str::startsWith($line, 'END RequestId:')) {
                    return null;
                }

                if (Str::startsWith($line, 'REPORT RequestId')) {
                    $reportLineReached = true;

                    return $this->parseReportLine($line);
                }

                if ($line === '') {
                    return null;
                }

                return $this->parseInfoLine($line);
            } catch (Throwable $exception) {
                return $this->unknownLine($line);
            }
        }, $lines);

        return array_values(array_filter($lines));
    }

    protected function parseStartLine($line)
    {
        $this->requestId = Str::after(
            Str::before($line, ' Version'),
            'START RequestId: '
        );
    }

    protected function parseInfoLine($line)
    {
        $parts = explode("\t", $line);

        if (count($parts) < 4) {
            return $this->unknownLine($line);
        }

        $body = $parts[3];

        if ($body === 'Invoke Error ') {
            $body .= $parts[4];
        }

        return [
            'timestamp' => Carbon::make($parts[0])->timestamp,
            'level' => $parts[2],
            'body' => $body
        ];
    }

    protected function unknownLine($line)
    {
        return [
            'timestamp' => now()->timestamp,
            'level' => 'UNKNOWN',
            'body' => $line,
        ];
    }

    protected function parseReportLine($line)
    {
        $parts = array_filter(explode("\t", $line));
        array_shift($parts);

        $this->report = collect($parts)->mapWithKeys(function ($part) {
            [$key, $value] = explode(': ', $part);

            return [$key => $value];
        })->toArray();
    }

    protected function unitlessNumberFromReport($key)
    {
        if (!Arr::has($this->report, $key)) {
            return;
        }

        // Coerce from a string into either a float or an int.
        return 0 + head(explode(' ', $this->report[$key]));
    }
}
