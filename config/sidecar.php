<?php

return [
    /*
     * All of your function classes that you'd like to deploy go here.
     */
    'functions' => [
        // \App\Sidecar\RenderOgImage::class,
        // \App\Sidecar\ProcessThumbnail::class,
    ],

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                                                                       *
     *  You are welcome to edit this configuration directly, or you can run  *
     *  `php artisan sidecar:configure` for an interactive walk-through.     *
     *                                                                       *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /*
     * Your AWS key. See CreateDeploymentUser::policy for the IAM policy.
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
     * The region where your Lambdas will be deployed.
     */
    'aws_region' => env('SIDECAR_REGION'),

    /*
     * The bucket that temporarily holds your function's ZIP files as they
     * are deployed to Lambda. It must be the same region as your Lambdas.
     */
    'aws_bucket' => env('SIDECAR_ARTIFACT_BUCKET_NAME'),

    /*
     * This is the execution role that your Lambdas will use.
     *
     * See CreateExecutionRole::policy for the IAM policy.
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

    /*
     * The base path for your package files. If you e.g. keep
     * all your Lambda package files in your resource path,
     * you may change the base path here.
     */
    'package_base_path' => env('SIDECAR_PACKAGE_BASE_PATH', base_path()),
];
