<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Commands\Actions;

use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Throwable;

class CreateBucket extends BaseAction
{
    /**
     * @var S3Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @return string
     */
    public function invoke()
    {
        $this->client = $this->command->client(S3Client::class);

        $this->bucket = config('sidecar.aws_bucket') ?? $this->defaultBucketName();

        $this->ensureBucketIsPrefixed();

        $this->progress("Using bucket name `$this->bucket`");

        $this->progress('Checking to see if the bucket exists...');

        if ($this->bucketExists()) {
            $this->progress('Bucket already exists');

            return $this->bucket;
        }

        $this->progress('Bucket doesn\'t exist.');

        // Sometimes it takes a second for the AWS credentials to populate, so we will try a couple times.
        retry(3, function () {
            $this->progress('Trying to create bucket...');
            $this->createBucket();
        }, 4000);

        $this->progress('Bucket created');

        return $this->bucket;
    }

    protected function defaultBucketName()
    {
        $now = now()->timestamp;

        return "sidecar-{$this->region}-{$now}";
    }

    protected function ensureBucketIsPrefixed()
    {
        if (Str::startsWith($this->bucket, 'sidecar-')) {
            return;
        }

        $this->bucket = Str::start($this->bucket, 'sidecar-');

        $question = implode("\n", [
            'Your bucket name must begin with "sidecar-".',
            " Using the name `$this->bucket`. Is that ok?"
        ]);

        if (!$this->command->confirm($question, $default = true)) {
            throw new Exception('Unable to determine valid bucket name.');
        }
    }

    protected function bucketExists()
    {
        try {
            $this->client->headBucket([
                'Bucket' => $this->bucket,
            ]);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function createBucket()
    {
        try {
            $this->client->createBucket([
                'ACL' => 'private',
                'Bucket' => $this->bucket,
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => $this->region,
                ],
            ]);
        } catch (Throwable $e) {
            $this->command->error('Unable to create deployment artifact bucket.');

            throw $e;
        }
    }
}
