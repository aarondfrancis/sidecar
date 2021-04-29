<?php

return [
    /*
     * This is the execution role that your Lambdas will use. If you are using
     * Laravel Vapor, you already have a role created in AWS that's called
     * "laravel-vapor-role". If you'd like to use that role, set the
     * role_arn value to CONSTRUCT_FROM_VAPOR_VARIABLES.
     *
     * If you want to create your own role, you can modify the IAM Policy
     * template below to meet your needs:
     * {
     *     "Version": "2012-10-17",
     *     "Statement": [
     *         {
     *             "Action": [
     *                 "kms:Decrypt",
     *                 "secretsmanager:GetSecretValue",
     *                 "ssm:GetParameters",
     *                 "ssm:GetParameter",
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
    'role_arn' => env('SIDECAR_ROLE_ARN', 'CONSTRUCT_FROM_VAPOR_VARIABLES'),

    /*
     * Your AWS secret key. You can provide your own key by using the
     * SIDECAR_SECRET_ACCESS_KEY env variable, or leave it blank to
     * rely on the one Laravel Vapor uses.
     */
    'aws_secret' => env('SIDECAR_SECRET_ACCESS_KEY') ?? env('AWS_SECRET_ACCESS_KEY'),

    /*
     * Your AWS key. You can provide your own key by using the
     * SIDECAR_ACCESS_KEY_ID env variable, or leave it blank
     * to rely on the one Laravel Vapor uses.
     */
    'aws_key' => env('SIDECAR_ACCESS_KEY_ID') ?? env('AWS_ACCESS_KEY_ID'),

    /*
     * The region where your Lambdas will be deployed. Again, you
     * can provide your own or leave it blank to use the
     * same region Vapor uses.
     */
    'aws_region' => env('SIDECAR_REGION') ?? env('AWS_REGION'),

    /*
     * The bucket that temporarily holds your function's ZIP files for deployment
     * to Lambda. It must be in the same region as your Lambdas. You can
     * specify your own, or default to the same one Vapor uses.
     */
    'aws_bucket' => env('SIDECAR_ARTIFACT_BUCKET_NAME') ?? env('VAPOR_ARTIFACT_BUCKET_NAME'),

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
     * All of your function classes that you'd like to deploy go here.
     */
    'functions' => [
        // \App\Sidecar\RenderOgImage::class,
        // \App\Sidecar\ProcessThumbnail::class,
    ]
];