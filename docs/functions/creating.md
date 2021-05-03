
# Creating Functions

To create a new Sidecar function, you simply create a new PHP class that extends the abstract `Hammerstone\Sidecar\LambdaFunction`.

You'll be left with two methods that you need to implement, `handler` and `package`

```php
use Hammerstone\Sidecar\LambdaFunction;

class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        // TODO: Implement handler() method.
    }

    public function package()
    {
        // TODO: Implement package() method.
    }
}
```

## Function Handler

What you define for your handler depends on what runtime you're using.

In general, it's usually follows the format of `file.function`. For example, when using the Node runtimes, your handler is always `filename.named-export`. 

If you had the following file:

image.js {.filename}

```js
exports.handle = async function() {
    // 
}
```

Then your `handler` would be `image.handle`: 

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'image.handle'; // [tl! ~~]
    }

    public function package() // [tl! collapse-start closed]
    {
        // TODO: Implement package() method.
    } // [tl! collapse-end]
}
```

If you were deploying a Python function:


example.py {.filename}
```python
def handler_name(event, context): 
    return some_value
```

Then your handler would be changed to `example.handler_name`

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'example.handler_name'; // [tl! ~~]
    }

    public function package() // [tl! collapse-start closed]
    {
        // TODO: Implement package() method.
    } // [tl! collapse-end]
}
```

If your handler file is nested in a subdirectory, you can just prepend the path:

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'resources/js/image.handle'; // [tl! ~~]
    }

    public function package() // [tl! collapse-start closed]
    {
        // TODO: Implement package() method.
    } // [tl! collapse-end]
}
```

You can read more below about packaging, but all paths are relative to your application's `base_path`.

To read more about what the handler should be based on your runtime, see the following pages in the AWS documentation:

- [Python](https://docs.aws.amazon.com/lambda/latest/dg/python-handler.html)
- [Ruby](https://docs.aws.amazon.com/lambda/latest/dg/ruby-handler.html)
- [Java](https://docs.aws.amazon.com/lambda/latest/dg/java-handler.html)
- [Go](https://docs.aws.amazon.com/lambda/latest/dg/golang-handler.html)
- [C#](https://docs.aws.amazon.com/lambda/latest/dg/csharp-handler.html)
- [Node.js](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-handler.html)
- [PowerShell](https://docs.aws.amazon.com/lambda/latest/dg/powershell-handler.html)
- [Java](https://docs.aws.amazon.com/lambda/latest/dg/java-handler.html)


## Deployment Package

In order for your Lambda to run, you'll need at least _one_ file, that contains your handler. In reality, you'll likely have many dependencies that support your handler. Sidecar will gather all of those files and zip them up so that they can be delivered to Lambda.

Your only job is to define what files should be included, and optionally which ones should be excluded.

In its simplest use, you can just return an array from the `package` method.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse-start closed]
    {
        return 'image.handle';
    } // [tl! collapse-end]

    public function package()
    {
        return [ 
            'resources/lambda/image.js' // [tl! ~~]
        ]; 
    }
}
```

You can include entire directories. You can also _exclude_ files by prepending an exclamation mark `!`.
```php
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse-start closed]
    {
        return 'image.handle';
    } // [tl! collapse-end]

    public function package()
    {
        return [ 
            // Include the whole directory [tl! ~~] 
            'resources/lambda', // [tl! ~~]
            // Except this file [tl! ~~]
            '!resources/lambda/ignore.js' // [tl! ~~]
        ]; 
    }
}
```

### The Package Class

If you need a little more fine-grained control over the packaging process, you can use the `Package` class.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse-start closed]
    {
        return 'image.handle';
    } // [tl! collapse-end]

    public function package()
    {
        return Package::make()
            // Change the default base path.
            ->setBasePath(resource_path())
            ->include([
                'lambda'
            ])
            ->exclude([
                'lambda/ignore.js'
            ]);
    }
}
```

By default, all paths are relative to your application's `base_path()`. If you'd like to change that, you can use the `setBasePath` method shown above.

The Package class also has methods for explicitly including and excluding files, should you prefer that method.

To read more about what you can and should include in your package, based on your runtime, see the following pages in Amazon's documentation:

- [Python](https://docs.aws.amazon.com/lambda/latest/dg/python-package.html)
- [Ruby](https://docs.aws.amazon.com/lambda/latest/dg/ruby-package.html)
- [Java](https://docs.aws.amazon.com/lambda/latest/dg/java-package.html)
- [Go](https://docs.aws.amazon.com/lambda/latest/dg/golang-package.html)
- [C#](https://docs.aws.amazon.com/lambda/latest/dg/csharp-package.html)
- [Node.js](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-package.html)
- [PowerShell](https://docs.aws.amazon.com/lambda/latest/dg/powershell-package.html)
- [Java](https://docs.aws.amazon.com/lambda/latest/dg/java-package.html)

## Runtime

Lambda supports multiple languages through the use of runtimes. You can choose any of the following runtimes by returning its corresponding identifier: 

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
    public function handler() // [tl! collapse-start closed]
    {
        // 
    } // [tl! collapse-end]

    public function package() // [tl! collapse-start closed]
    {
        //
    } // [tl! collapse-end]
    
    public function runtime() 
    {
        return 'go1.x'; // [tl! ~~]
    }
}
```

Read more in the [AWS Documentation](https://docs.aws.amazon.com/lambda/latest/dg/lambda-runtimes.html).

## Name

Your function has a `name` method that determines how Sidecar names your Lambdas. By default it is based on the name of your function class. You are free to change this if you want, but you're unlikely to need to.

### Name Prefix

Regardless of what you choose for your function names, Sidecar will prepend the name of your app and the current environment. 

Lambda function names must be unique, so adding these variables to your function names prevents collisions between different apps and environments.

## Description

The description is totally up to you, you'll likely not need to change it at all. We have provided a descriptive default.

## Memory

The only compute-related configuration that AWS allows you to configure for your Lambda is memory. From [their documentation](https://docs.aws.amazon.com/lambda/latest/dg/configuration-memory.html):

> Lambda allocates CPU power in proportion to the amount of memory configured. Memory is the amount of memory available to your Lambda function at runtime. You can increase or decrease the memory and CPU power allocated to your function using the Memory (MB) setting. To configure the memory for your function, set a value between 128 MB and 10,240 MB in 1-MB increments. At 1,769 MB, a function has the equivalent of one vCPU (one vCPU-second of credits per second).

By default, Sidecar uses the value in your `sidecar.php` configuration file, which itself defaults to 512mb.

To change the allocated memory of your function, simply return the number in megabytes.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse-start closed]
    {
        // 
    } // [tl! collapse-end]

    public function package() // [tl! collapse-start closed]
    {
        //
    } // [tl! collapse-end]
    
    public function memory() 
    {
        // 2GB of memory [tl! ~~]
        return 2048; // [tl! ~~]
    }
}
```

Because this has cost implications, you should consider what makes the most sense for your use case.

## Timeout

Every Lambda function must specify a timeout value, at which point AWS will stop execution. There is a hard upper limit of 15 minutes.

By default Sidecar uses the value from you `sidecar.php` configuration file, which is defaulted to 300 seconds.

You are free to change that per function by returning a value from the `timeout` method.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse-start closed]
    {
        // 
    } // [tl! collapse-end]

    public function package() // [tl! collapse-start closed]
    {
        //
    } // [tl! collapse-end]
    
    public function timeout() 
    {
        // Only 30 seconds [tl! ~~]
        return 30; // [tl! ~~]
    }
}
```

## Layers

Some functions require extra code or data beyond what is in your code package. From [Amazon's documentation](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html): 

> A Lambda layer is a .zip file archive that can contain additional code or data. A layer can contain libraries, a custom runtime, data, or configuration files. Layers promote code sharing and separation of responsibilities so that you can iterate faster on writing business logic.

If you want to include layers in your Lambda, you'll need to provide the full ARN for those layers.

In this example below, we're providing the ARN for a layer that has Node Canvas pre-built for the Lambda runtime.
```php
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! collapse-start closed]
    {
        // 
    } // [tl! collapse-end]

    public function package() // [tl! collapse-start closed]
    {
        //
    } // [tl! collapse-end]
    
    public function timeout() 
    {
        return [
            // Node Canvas from https://github.com/jwerre/node-canvas-lambda [tl! ~~]
            'arn:aws:lambda:us-east-2:XXXX:layer:node_canvas:1', // [tl! ~~]
        ];       
    }
}
```

Note that your layers must be in the same AWS region as your Lambdas!

## Interacting with Deployment

Sidecar dispatches global [events](/events) as it's deploying and activating functions. It also calls instance methods on each function, should you care to do anything at that time.

The following four methods are available to you:

- `beforeDeployment`
- `afterDeployment`
- `beforeActivation`
- `afterActivation`