
# Environments

Sidecar functions are separated by environment, so that your local development functions don't overwrite your production functions. You can have as many environments as you please.

By default, Sidecar will use the environment from your Laravel application, which itself comes from the `APP_ENV` environment variable.

If you'd rather use a dedicated environment variable for Sidecar, you can use the `SIDECAR_ENV` variable.

```php
<?php

return [
    /*
     * Sidecar separates functions by environment. If you'd like to change
     * your Sidecar environment without changing your entire application
     * environment, you may do so here.
     */
    'env' => env('SIDECAR_ENV', env('APP_ENV')),
];
```

This can be particularly useful when you're developing in a team. If everyone on your team has `APP_ENV=local` in their environment file, then it's likely that functions will inadvertently be overwritten by your teammates, which is both confusing and frustrating.

In this case, it would make sense to set your Sidecar environment to e.g. `SIDECAR_ENV=aaron_local`, `SIDECAR_ENV=sean_local`, etc.

This way Sean & Aaron have different environments and won't overwrite each other's work.

## Faking the Environment
 
If you need to deploy an environment other than the one you are in, you can override the environment from the config by passing an `--env` flag to the Deploy and Activate commands.

```shell
php artisan sidecar:deploy --env=production
php artisan sidecar:activate --env=production
```

This is useful when you want to build and deploy from either your local machine, your CI pipeline, or GitHub Actions, and then you want to activate as your Laravel application rolls out.

Head over to the [Deploying section](functions/deploying) to read more about activating & deploying your functions.   