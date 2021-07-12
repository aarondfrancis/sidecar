
# Configuring Sidecar

After running `php artisan sidecar:install`, you should have a `sidecar.php` file in your `config` folder. 

There are several configuration options in that file, which we'll cover in this section.

## AWS Credentials

Sidecar requires a few very specific things be set up in your AWS in order to have the proper permissions to deploy and execute your functions.

In order to save you from the frustration of using AWS IAM we have written a single, interactive command that can handle everything for you.

To get started, run the following command:

```shell
php artisan sidecar:configure
```

The first thing it will do is guide you through creating a new AWS user in the web interface, which it will then use to create everything else it needs. 

Note that this won't start any services, it just creates some policies in IAM.

This is the same general method that Laravel Vapor uses: you provide it with Admin Access and then it configures itself. Sidecar takes it a step further and provides you the option to self-destruct the admin keys once it has configured itself. 

If you'd like to manually set everything up, take a look at the command to see exactly what it's doing, and you can recreate it in the IAM portal.

## Registering Functions

Each function that you make will need to be registered in the `functions` key of your `sidecar.php`

config/sidecar.php {.filename}
```php
return [
    /*
     * All of your function classes that you'd like to deploy go here.
     */
    'functions' => [
        \App\Sidecar\OgImage::class,
        \App\Sidecar\ProcessThumbnail::class,
    ],
];
```

## Function Timeout & Memory

The timeout and memory can be customized on a per-function basis, but if they aren't, the defaults from your `sidecar.php` file will be used.

```php
return [
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

## Package Base Path

By default, all of your Lambda resources are going to be relative to the `base_path()` of your application. That means when you're defining your code packages, you'll use the root of your application as the starting point. 

If all of your Lambda code lives in e.g. `resources/lambda`, then you can update your `package_base_path` to reflect that.

config/sidecar.php {.filename}
```php
return [ 
    /*
     * The base path for your package files. If you e.g. keep
     * all your Lambda package files in your resource path,
     * you may change the base path here.
     */
    'package_base_path' => env('SIDECAR_PACKAGE_BASE_PATH', base_path()),
];
```

This is also configurable on a per-function basis. To learn more about that, see the [Handlers & Packages](/functions/handlers-and-packages) section.

## Environment

Sidecar separates functions by environment so that your development, staging, and production functions do not overwrite each other.

By default, the environment name that Sidecar uses is your `APP_ENV` from your `.env` file. This usually works great for staging and production, but if you are working on a team, you'll have multiple people using an environment named `local`, potentially interfering with one another.

If you'd like to use something other than the `APP_ENV`, you can do so by providing a `SIDECAR_ENV` environment variable.

```php
return [ 
    /*  
     * Sidecar separates functions by environment. If you'd like to change
     * your Sidecar environment without changing your entire application
     * environment, you may do so here.
     */
    'env' => env('SIDECAR_ENV', env('APP_ENV')),
];
```
    
To learn much more about environments and how to use them, see the [Environments](/environments) section.