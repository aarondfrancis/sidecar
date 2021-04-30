<?php

return [
    /*
     * All of your function classes that you'd like to deploy go here.
     */
    'functions' => [
        // \App\Sidecar\RenderOgImage::class,
        // \App\Sidecar\ProcessThumbnail::class,
    ],

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                                                                     *
     *  You are welcome to edit this configuration directly, or you can    *
     *  run `php artisan sidecar:env` for an interactive walk-through.   *
     *                                                                     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


    /*
     * Your AWS key. It will need the following permissions (at a minimum):
     * {
     *     "Version": "2012-10-17",
     *     "Statement": [
     *         {
     *             "Effect": "Allow",
     *             "Action": [
     *                 "s3:*",
     *                 "lambda:*"
     *             ],
     *             "Resource": "*"
     *         }
     *     ]
     * }
     *
     * Unfortunately you cannot rely on the keys available in the Vapor
     * runtime, as those do not have the right permissions.
     */
    'aws_key' => env('SIDECAR_ACCESS_KEY_ID'),

    /*
     * Your AWS secret key.
     */
    'aws_secret' => env('SIDECAR_SECRET_ACCESS_KEY'),

    /*
     * The region where your Lambdas will be deployed. You can provide
     * your own or leave it blank to use the same region Vapor uses.
     */
    'aws_region' => env('SIDECAR_REGION'),

    /*
     * The bucket that temporarily holds your function's ZIP files for deployment
     * to Lambda. It must be in the same region as your Lambdas. You can
     * specify your own, or default to the same one Vapor uses.
     */
    'aws_bucket' => env('SIDECAR_ARTIFACT_BUCKET_NAME'),

    /*
     * This is the execution role that your Lambdas will use.
     *
     * If you want to create your own role, you can modify the IAM Policy
     * template below to meet your needs:
     * {
     *     "Version": "2012-10-17",
     *     "Statement": [
     *         {
     *             "Action": [
     *                 "lambda:invokeFunction",
     *                 "s3:*",
     *                 "ses:*",
     *                 "sqs:*",
     *                 "dynamodb:*"
     *             ],
     *             "Effect": "Allow",
     *             "Resource": "*"
     *         }
     *     ]
     * }
     */
    'execution_role' => env('SIDECAR_EXECUTION_ROLE'),

    /*
     * The default timeout for your functions, in seconds.
     * This can be overridden per function.
     */
    'timeout' => env('SIDECAR_TIMEOUT', 300),

    /*
     * The default memory for your functions, in megabytes.
     * This can be overridden per function.
     */
    'memory' => env('SIDECAR_MEMORY', 512),
];