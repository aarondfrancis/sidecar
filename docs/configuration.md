
# Configuring Sidecar

## Configuring AWS

Sidecar requires a few very specific things be set up in your AWS in order to have the proper permissions to deploy and execute your functions.

In order to save you from the frustration of AWS IAM, we have written a single, interactive command that can handle everything for you.

To get started, run the following command:

```text
php artisan sidecar:configure
```

The first thing it will do is guide you through creating a new AWS user, which it will then use to create everything else it needs. 

Note that this won't start any services, it just creates some policies in IAM.

This is the same method that Vapor uses: you provide it with Admin Access and then it configures itself.

If you'd like to manually set everything up, take a look at the command to see exactly what it's doing, and you can recreate it in the IAM portal.

## Registering Functions

Each function that you make will need to be registered in the `functions` key of your `sidecar.php`

sidecar.php {.filename}
```php
return [
    /*
     * All of your function classes that you'd like to deploy go here.
     */
    'functions' => [
        \App\Sidecar\OgImage::class,
        \App\Sidecar\ProcessThumbnail::class,
    ],
    // [tl! collapse-start closed]
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
    'memory' => env('SIDECAR_MEMORY', 512), // [tl! collapse-end]
];
```

## Default Settings

The timeout and memory can be customized on a per-function basis, but if they aren't, the defaults from your `sidecar.php` file will be used.

sidecar.php {.filename}
```php
return [ 
    // [tl! collapse-start closed]
    /*
     * All of your function classes that you'd like to deploy go here.
     */
    'functions' => [
        \App\Sidecar\OgImage::class,
        \App\Sidecar\ProcessThumbnail::class,
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
    // [tl! collapse-end]
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
```
