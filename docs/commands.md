
# Commands

Sidecar comes with a few CLI commands to make your life easier.

## Install

The install command publishes your `sidecar.php` configuration file. Once that file is published, you will no longer see it in your list of available commands.

```text
php artisan sidecar:install
```

## Configure

The configure command is an interactive command that walks you through setting up your AWS credentials. Dealing with AWS IAM can be a pain, so we wrote this command to do it for you.

```text
php artisan sidecar:configure
```

## Make

The make command will create a new function class in your `app/Sidecar` directory.

```text
php artisan make:lambda-function MyFunction
```

You can also pass a `--runtime=` flag to specify the runtime you want to use. The default runtime for newly created functions is `nodejs20.x`.
To see a list of available runtimes, see the [Runtime](functions/customization#runtime) section.

```text
php artisan make:lambda-function MyFunction --runtime=python3.10
```

## Deploy

The deploy command deploys your functions to Lambda, and can optionally activate them.

To deploy but not activate, run the command without any arguments.

```text
php artisan sidecar:deploy
```

This will create your Lambda function but Sidecar will not use this version until you activate it. This give you time to deploy your entire application and flip the switch at the very end. To read more about this, see the [Deploying vs Activating](functions/deploying#user-content-deploying-vs-activating) section.

If you want to deploy _and_ activate, you can pass the `--activate` flag.

```text
php artisan sidecar:deploy --activate
```

## Activate

The activate command will activate the latest version of all of your functions to be used by Sidecar.

```text
php artisan sidecar:activate
```


## Overriding the Environment

If you need to deploy an environment other than the one you are running in, you can override the environment from the config by passing an `--env` flag to the Deploy and Activate commands.

```text
php artisan sidecar:deploy --env=production
php artisan sidecar:activate --env=production
```