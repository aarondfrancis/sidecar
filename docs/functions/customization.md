
# Customizing Functions

The only two things _required_ for a Sidecar function are the [package and the handler](handlers-and-packages), but there are many more things you can customize about your functions to meet your specific needs.

## Runtime

Lambda supports multiple languages through the use of runtimes. You can choose any of the following runtimes by returning its corresponding identifier:

- Node.js 16: `nodejs16.x`
- Node.js 14: `nodejs14.x`
- Node.js 12: `nodejs12.x`
- Node.js 10: `nodejs10.x`
- Python 3.8: `python3.8`
- Python 3.7: `python3.7`
- Python 3.6: `python3.6`
- Python 2.7: `python2.7`
- Ruby 2.7: `ruby2.7`
- Ruby 2.5: `ruby2.5`
- Java 11: `java11`
- Java 8: `java8`
- Go 1.x: `go1.x`
- .NET Core 3.1: `dotnetcore3.1`
- .NET Core 2.1: `dotnetcore2.1`

E.g. to use the Go runtime, you would return `go1.x`:

```php
class ExampleFunction extends LambdaFunction
{
    public function runtime() // [tl! focus:3]
    {
        return 'go1.x';
    }
}
```

Read more in the [AWS Documentation](https://docs.aws.amazon.com/lambda/latest/dg/lambda-runtimes.html).

## Memory

The only compute-related configuration that AWS allows you to configure for your Lambda is memory. From [their documentation](https://docs.aws.amazon.com/lambda/latest/dg/configuration-memory.html):

> Lambda allocates CPU power in proportion to the amount of memory configured. Memory is the amount of memory available to your Lambda function at runtime. You can increase or decrease the memory and CPU power allocated to your function using the Memory (MB) setting. To configure the memory for your function, set a value between 128 MB and 10,240 MB in 1-MB increments. At 1,769 MB, a function has the equivalent of one vCPU (one vCPU-second of credits per second).

By default, Sidecar uses the value in your `sidecar.php` configuration file, which itself defaults to 512mb.

To change the allocated memory of your function, return the number in megabytes.

```php
class ExampleFunction extends LambdaFunction
{
    public function memory() //  [tl! focus:4]
    {
        // 2GB of memory
        return 2048;
    }
}
```

Because this has cost implications, you should consider what makes the most sense for your use case.

## Timeout

Every Lambda function must specify a timeout value, at which point AWS will stop execution. There is a hard upper limit of 15 minutes.

Sidecar uses the value from you `sidecar.php` configuration file, which is defaulted to 300 seconds.

You are free to change that per function by returning a value from the `timeout` method.

```php
class ExampleFunction extends LambdaFunction
{
    public function timeout() // [tl! focus:4]
    {
        // Only 30 seconds
        return 30;
    }
}
```

## Storage

Lambda functions can configure the amount of ephemeral storage available to them in the `/tmp` directory. This storage is shared between function instances, which means it persists across _invocations_ of a single warm Lambda function instance but is cleaned up everywhere else (e.g. between cold starts, when scaling up to more concurrent invocations, etc.).

Sidecar uses the storage value from you `sidecar.php` configuration file, which defaults to 512MB.

You are free to change this per function by returning a value from the `storage` method.

```php
class ExampleFunction extends LambdaFunction
{
    public function storage() // [tl! focus:4]
    {
        // 2 GB
        return 2048;
    }
}
```

## Layers

Some functions require extra code or data beyond what is in your code package. From [Amazon's documentation](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html):

> A Lambda layer is a .zip file archive that can contain additional code or data. A layer can contain libraries, a custom runtime, data, or configuration files. Layers promote code sharing and separation of responsibilities so that you can iterate faster on writing business logic.

If you want to include layers in your Lambda, you'll need to provide the full ARN for those layers.

In this example below, we're providing the ARN for a layer that has [Node Canvas pre-built](https://github.com/jwerre/node-canvas-lambda) for the Lambda runtime.
```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        //
    }

    public function package()
    {
        //
    }

    public function layers() // [tl! focus:start]
    {
        return [
            // Node Canvas from https://github.com/jwerre/node-canvas-lambda
            'arn:aws:lambda:us-east-2:XXXX:layer:node_canvas:1',
        ];
    } // [tl! focus:end]
}
```

Note that your layers must be in the same AWS region as your Lambdas!

## Environment Variables

Some functions or layers may require configuration via Lambda environment variables. The Lambda runtime will inject the environment variables so that they available to your handler code.

In this example below, we're providing a path to a font directory as an environment variable.
```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        //
    }

    public function package()
    {
        //
    }

    public function variables() // [tl! focus:start]
    {
        return [
            'FONTCONFIG_PATH' => '/opt/etc/fonts',
        ];
    } // [tl! focus:end]
}
```

It is **very important** to note that if you allow Sidecar to manage your Lambda's environment variables, any changes made to environment variables in the AWS UI will be overwritten next time you deploy your function.

By default, Sidecar doesn't touch your Lambda's environment at all. Only when you return an array from `variables` will Sidecar take control of the env vars.

Another important thing to note is that Sidecar sets your environment variables as a part of the _activation_ process, not the _deploy_ process. This means that the environment variables will be pulled from the machine that calls `sidecar:activate`.

For example, if you are sharing secrets with your Lambda, then the values passed to your Lambda will be equal to the ones on the machine that called `sidecar:activate`, not the one that called `sidecar:deploy`

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        //
    }

    public function package()
    {
        //
    }

    public function variables()  // [tl! focus:start]
    {
        // Values will come from the "activating" machine.
        return [
            'aws_key' => config('services.aws.key'),
            'aws_secret' => config('services.aws.secret'),
        ];
    } // [tl! focus:end]
}
```

This is obviously important when you are calling `deploy` and `activate` from different machines as a part of your Laravel application's deployment process. To read more about deployment strategies, check out the [deploying](deploying) section.

## Name

Your function has a `name` method that determines how Sidecar names your Lambda functions. By default it is based on the name and path of your PHP class. You are free to change this if you want, but you're unlikely to need to.

```php
class ExampleFunction extends LambdaFunction
{
    public function name() // [tl! focus:3]
    {
        return 'Function Name';
    }
}
```

### Name Prefix

Regardless of what you choose for your function names, Sidecar will prepend the name of your app and the current environment.

Lambda function names must be unique, so adding these variables to your function names prevents collisions between different apps and environments.

You likely won't need to change this, but if you do, *you must include the environment*. If you don't, your local and production functions will overwrite each other.


```php
    class ExampleFunction extends LambdaFunction
    {
        public function prefix() // [tl! focus:4]
        {
            // Don't forget the environment!
            return 'My App ' . Sidecar::getEnvironment()
        }
    }
```

## Description

The description is totally up to you, you'll likely not need to change it at all. We have provided a descriptive default. Sidecar doesn't do anything with it.
