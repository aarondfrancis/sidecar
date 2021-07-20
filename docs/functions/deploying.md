
# Deploying Functions

After you have created your function class and all the handler code, you'll need to deploy it to AWS Lambda.

The simplest way to deploy and activate all of your configured functions is to run the following command

```shell
php artisan sidecar:deploy --activate
```

When you run that, you'll see an output log similar to the one below:

```text
[Sidecar] Deploying App\Sidecar\OgImage to Lambda as `SC-Laravel-local-Sidecar-OgImage`.
          ↳ Environment: local
          ↳ Runtime: nodejs12.x
          ↳ Packaging function code.
          ↳ Creating a new zip file.
          ↳ Zip file created at s3://sidecar-us-east-2-XXX/sidecar/001-79a5915eaec296be04a0f4fb7cc80e40.zip
[Sidecar] Activating function App\Sidecar\OgImage.
          ↳ Activating Version 1 of SC-Laravel-local-Sidecar-OgImage.
```

The deployment process consists of 
- zipping the handler code
- uploading the zip file to S3
- creating the Lambda function if it doesn't exist
- updating the function if it already exists
- (optionally) activating the newest version of the function

## Deploying vs. Activating

There are two required steps in order to make your functions live and usable.
- The first is to **deploy** the handler code and create the Lambda function.
- The second step is to **activate** that new version of your Lambda function.

Because your handler code may require you to `npm install`, `bundle install`, or something similar, you may be building and deploying your handler code in CI or from your local computer.

Once that handler code is all bundled and deployed to Lambda, there may be a secondary step required to deploy your main application. 

During this secondary step, your Lambda functions are going to be updated but your application code will not be, which could lead to errors for your users.

That's why we have separated the two steps into Deploy and Activate.

To deploy but not activate your code, you would run the deploy command without the `--activate` flag.

```shell
php artisan sidecar:deploy
```

Then, when it is time to flip the switch over to your new application code, you can call `sidecar:activate` to activate the newest version of all your Sidecar functions on Lambda.

```shell
php artisan sidecar:activate
```

## A Vapor Example

Laravel Vapor provides you with [two hooks](https://docs.vapor.build/1.0/projects/deployments.html#build-hooks) during its deploy process. The first is the `build` hook, where you can install all of your dependencies, run any scripts, etc, _on the machine that is running the deployment._

The second step is the `deploy` hook, that is _run in the Vapor environment_.

You might set your `vapor.yml` up to use those hooks to deploy and activate your functions:

```yaml
id: XXX
name: hammerstonedev
environments:
  production:
    domain: hammerstone.dev
    memory: 512
    cli-memory: 512
    build: # [tl! ~~]
      - 'php artisan sidecar:deploy --env=production' # [tl! ~~]
    deploy: # [tl! ~~]
      - 'php artisan sidecar:activate' # [tl! ~~]
```  

Your functions would be built and deployed on whatever machine is handling the Vapor deploy process, and then would be activated as Vapor activates your newest application code.

> Note that in this example we're setting the environment to "production" in the build, because it's likely that your "build" step is running in an environment that doesn't have it's ENV set to "production." See below for more details.

## Faking the Environment
 
Sidecar names your function based on environment, to prevent collisions between local, staging, production, etc. This could pose a problem if you are deploying your production functions from your build or CI server. 

If you need to deploy an environment other than the one you are in, you can override the environment from the config by passing an `--env` flag to the Deploy and Activate commands.

```shell
php artisan sidecar:deploy --env=production
php artisan sidecar:activate --env=production
```

To read more about environments, head to the [Environments section](../environments) of the docs.

## Setting Environment Variables

This is covered in the [Environment Variables](customization#environment-variables) section of the Customization docs, but we'll cover strategies around env vars and deployment here. 

Some of your functions will require environment variables, either from your Laravel application or something completely distinct. In some cases, you may need to set a static variable so that some library will work [(LibreOffice example)](https://github.com/hammerstonedev/sidecar/issues/24):

```php
// torchlight! {"summaryCollapsedIndicator": "{ ... }" }
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse:start]
    {
        //
    }

    public function package()
    {
        //
    } // [tl! collapse:end]

    public function variables()
    {
        return [
            'FONTCONFIG_PATH' => '/opt/etc/fonts',
        ];
    }
}
```

Setting static variables like that is quite straightforward.

If, however, you want to share some secrets or environment variables _from your Laravel_ application, it will be important that you are sharing them from the correct environment.

Imagine the scenario where you want to share your AWS keys with your Lambda function:

```php
// torchlight! {"summaryCollapsedIndicator": "{ ... }" }
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse:start]
    {
        //
    }

    public function package()
    {
        //
    } // [tl! collapse:end]

    public function variables()
    {
        return [
            'aws_key' => config('services.aws.key'),
            'aws_secret' => config('services.aws.secret'),
        ];
    }
}
```

You pull your key and secret using the Laravel `config` function, which in turn likely delegates to the `env()` function.

Sidecar sets the environment variables _upon activation_. If you are deploying from CI and then activating from e.g. Vapor, you'll want to make sure you have those variables in the Vapor environment.

Environment variables are set before activation, and before any pre-warming takes place.

And remember! If Sidecar manages the environment variables for a function, it will clobber any changes you make in the AWS UI, so you cannot use both methods simultaneously.  

## Reusing Package Files

If you're deploying your Sidecar functions every time you deploy your app, you will likely be deploying functions that have not changed at all.

In the case where no code has changed, Sidecar will not upload a new zip file to your bucket, but will reuse the old one.

In the event that neither the code nor function configuration have changed, Sidecar won't touch the Lambda function at all!

You will see output similar to the following:

```text
[Sidecar] Deploying App\Sidecar\OgImage to Lambda. (Runtime nodejs12.x.)
          ↳ Function already exists, potentially updating code and configuration.
          ↳ Packaging function code.
          ↳ Package unchanged, reusing previous code package at s3://sidecar-us-east-2-XXX/sidecar/001-79a5915eaec296be04a0f4fb7cc80e40.zip.
          ↳ Function code and configuration are unchanged! Not updating anything. [tl! focus]
[Sidecar] Activating function App\Sidecar\OgImage.
          ↳ Activating Version 1 of SC-Laravel-local-Sidecar-OgImage.
``` 




