# Handlers and Packages

To create a new Sidecar function, you must create a new PHP class that extends the abstract `Hammerstone\Sidecar\LambdaFunction`.

Every Lambda function requires at least two things: 

- the name of handler function
- the file or files needed to execute that handler function

Because these two things are required, these are the two abstract methods that you must implement in your Sidecar function class. 

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

The `handler` function should return a string that points to the function that is your entry point for the function. 

The format of your handler depends on the runtime that you're using, but in general it follows the format of `filename.function`. 

For example, when using the Node runtimes your handler is always `path/to/file.named-export`. If you had the following file:

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
    public function handler()  // [tl! focus:4]
    {
        // "image" is the filename, "handle" is the function.
        return 'image.handle';
    }

    public function package() 
    {
        // TODO: Implement package() method.
    } 
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
    public function handler() // [tl! focus:3]
    {
        return 'example.handler_name'; 
    }

    public function package()
    {
        // TODO: Implement package() method.
    }
}
```

If your handler file is nested in a subdirectory, you can prepend the path:

```php
class ExampleFunction extends LambdaFunction
{
    public function handler() // [tl! focus:3]
    {
        return 'resources/lambda/image.handle'; 
    }

    public function package()
    {
        // TODO: Implement package() method.
    }
}
```

By default, all paths are relative to your application's `base_path`. Continue reading for tips on how to customize that.

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

In order for your Lambda to run, you'll need at least the one file that contains your handler. In reality, you'll likely have many dependencies that support your handler. Sidecar will gather all of those files and zip them up so that they can be delivered to Lambda.

As the developer your only job is to define what files should be included, and optionally which ones should be excluded.

In its simplest use, you can return an array from the `package` method.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'image.handle';
    }

    public function package() // [tl! focus:5]
    {
        return [ 
            'resources/lambda/image.js'
        ]; 
    }
}
```

You can include entire directories. You can also _exclude_ files by prepending an exclamation mark `!`.
```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'image.handle';
    }

    public function package() // [tl! focus:start]
    {
        return [ 
            // Include the whole directory  
            'resources/lambda',
            
            // But not this file
            '!resources/lambda/ignore.js'
        ]; 
    } // [tl! focus:end]
}
```

## The Package Class

If you need a little more fine-grained control over the packaging process, you can use the `Package` class instead of the simpler array format.

Continuing on our example above, you can pass a set of paths into the constructor or the static `make` method.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'image.handle';
    }

    public function package() // [tl! focus:start]
    {
        return Package::make([
            // Include the whole directory  
            'resources/lambda',
            
            // But not this file
            '!resources/lambda/ignore.js'
        ]);
    } // [tl! focus:end]
}
```

By default, all paths are relative to your application's `base_path()`. If you'd like to change that, you can use the `setBasePath` method.

Note that you'll need to set the base path _before_ you pass in any paths, so you'll need to use the `include` and `exclude` methods instead of passing paths through the constructor.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'image.handle';
    }

    public function package() // [tl! focus:start]
    {
        return Package::make()
            // Set the base path to the application's resource path.
            ->setBasePath(resource_path())
            ->include([
                // Include the whole directory  
                'lambda',
            ])
            ->exclude([
                // But not this file
                'lambda/ignore.js'
            ]);
    } // [tl! focus:end]
}
```

If you'd like to include the every file in your configured `basePath`, you can pass in `*` (asterisk) as a special path.

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        return 'image.handle';
    }

    public function package() // [tl! focus:start]
    {
        return Package::make()
            // Set the base path to the a folder named `lambda` 
            // in the application's resource path.
            ->setBasePath(resource_path('lambda'))
            // Include that whole directory.
            ->include('*');
    } // [tl! focus:end]
}
```

## Package Reuse

As Sidecar is building your package to be uploaded to S3, it creates a hash of the contents of every file included in the package. If Sidecar determines that this exact package has already been built, it will reuse the zip file that exists on S3 instead of creating a new one.

## Package Limitations

There is a 250mb hard upper limit of imposed by Amazon on the size of your Lambda package. That means that when your package is _uncompressed_ it must be smaller that 250mb, _including_ all of your layers.

You can read it the official docs [here](https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html) or in a good real-world boundary-testing article [here](https://hackernoon.com/exploring-the-aws-lambda-deployment-limits-9a8384b0bec3).

Note that because Sidecar uses the S3-to-Lambda method, the 250mb limit applies, not the 50mb direct upload limit. 

## Strategies for Dealing With node_modules

With the 250mb limitation in mind, you may need to consider some alternate strategies regarding your `node_modules` directory, as it can easily grow beyond 250mb with very little effort on your part.

If shipping your entire `node_modules` folder is viable, that is certainly the easiest route.

### Separate Modules

Since your Lambda functions live inside your main Laravel application, it may be tempting to `npm install [something]` from your app's base directory and add the new dependency to your root `package.json`.

However, this means that you'll have to ship your _application's_ `node_modules` directory, which will undoubtedly contain lots of modules you don't need.

Consider instead making a new `package.json` solely for your Lambda functions. If you store all of your Lambda-specific code in `resources/lambda`, consider having a `package.json` and `node_modules` in that directory, containing only the modules needed for your functions to run, not the modules required by your Laravel application.  

- app {.folder}
- bootstrap {.folder}
- config {.folder}
- database {.folder}
- node_modules <span class="comment">← Modules for your main application</span> {.folder}
- public {.folder}
- resources {.folder.open}
    - css {.folder}
    - lambda {.folder.open}
        - image.js {.file}
        - node_modules <span class="comment">← Modules just for your Lambda</span> {.folder}
        - package.json {.file}
    - js {.folder}
- routes {.folder}
- storage {.folder}
- tests {.folder}
- vendor {.folder}
- package.json {.file}
{.files}

This will give you a much smaller `node_modules` directory, that you may be able to ship directly to Lambda.

### Compiling Your Handler with NCC

Sometimes even when you separate your `node_modules`, you may still end up with hundreds of megabytes of module code that you don't necessarily need. If that's the case, your best option is going to be to use a tool called [NCC](https://github.com/vercel/ncc) to compile your entire handler + supporting code down to a _single_ file.

NCC is a tool developed by Vercel for the explicit purposes of compiling Node.js modules into a single file. From their readme:

> [A] simple CLI for compiling a Node.js module into a single file, together with all its dependencies, gcc-style. 

NCC is a wrapper around [webpack](https://webpack.js.org/) that finds all the code needed to execute your handler and rolls it up into a single file.

Continuing with the example from earlier, you would develop your `image.js` handler the same as you have been, and then run NCC over it:

```shell
ncc build resources/lambda/image.js -o resources/lambda/dist
```

This will produce a `dist` folder in your lambda folder.
 
- app {.folder}
- bootstrap {.folder}
- config {.folder}
- database {.folder}
- node_modules {.folder}
- public {.folder}
- resources {.folder.open}
    - css {.folder}
    - lambda {.folder.open}
        - dist <span class="comment">← Built by NCC</span> {.folder.open}
            - index.js <span class="comment">← Your handler code + all supporting modules</span> {.file}
        - image.js {.file}
        - node_modules {.folder}
        - package.json {.file}
    - js {.folder}
- routes {.folder}
- storage {.folder}
- tests {.folder}
- vendor {.folder}
- package.json {.file}
{.files} 

And you'd now update your Sidecar class to point to the `dist` folder:

```php
class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        // NCC outputs a single file named "index", not "image". 
        return 'index.handle';
    }

    public function package() 
    {
        return Package::make()
            // Set the base path to the NCC built folder
            ->setBasePath(resource_path('lambda/dist'))
            // Include the whole directory.
            ->include('*');
    }
}
```

Because NCC takes a single file as input and generates a single file as an output, you will need to put all of your handlers in one file if you have multiple functions. 

If you had three functions named `image`, `screenshot`, and `thumbnail`, you might have a file called `handlers.js` that only exists to export all of your handlers:

lambda/handlers.js {.filename}
```js
const Image = require('./handlers/image');
const Screenshot = require('./handlers/screenshot');
const Thumbnail = require('./handlers/thumbnail');

exports.image = Image.handle;
exports.screenshot = Screenshot.handle;
exports.thumbnail = Thumbnail.handle;
```

Now you can run NCC on the handlers file:


```shell
ncc build resources/lambda/handlers.js -o resources/lambda/dist
```

And update your Sidecar function to point to the specific named export:

```php
class ScreenshotFunction extends LambdaFunction
{
    public function handler()
    {
        // The export named "screenshot"
        return 'index.screenshot'; // [tl! highlight]
    }

    public function package() 
    {
        return Package::make()
            // Set the base path to the NCC built folder
            ->setBasePath(resource_path('lambda/dist'))
            // Include the whole directory.
            ->include('*');
    }
}
```

## Container Images

In addition to the standard runtimes AWS Lambda offers the ability to package your Lambda function code and dependencies as a container image of up to 10 GB in size. 

To use a container image with Sidecar you must first build a docker image and push it to the Amazon Elastic Container Registry (ECR). See [the official docs](https://docs.aws.amazon.com/lambda/latest/dg/images-create.html) for step-by-step instructions.

Once the container has been added to the registry update the function's `package` method to return the container's ECR Image URI as shown below. Finally, add the `packageType` method with a return value of 'Image'. Note that you do not need to return anything from the `handler` method.

```php
use Hammerstone\Sidecar\LambdaFunction;

class ExampleFunction extends LambdaFunction
{
    public function handler()
    {
        
    }

    public function package()
    {
        return [
            'ImageUri' => '123456789012.dkr.ecr.us-east-1.amazonaws.com/hello-world:latest',
        ];
    }
    
    public function packageType()
    {
        return 'Image';
    }
}
```

With the above configuration in place you can create and activate the AWS Lambda function with the `artisan sidecar:deploy --activate` command. From there you can use Sidecar to interact with the container image in the same manner as traditional runtimes.

## Further Reading

To read more about what you can and should include in your package, based on your runtime, see the following pages in Amazon's documentation:

- [Python](https://docs.aws.amazon.com/lambda/latest/dg/python-package.html)
- [Ruby](https://docs.aws.amazon.com/lambda/latest/dg/ruby-package.html)
- [Java](https://docs.aws.amazon.com/lambda/latest/dg/java-package.html)
- [Go](https://docs.aws.amazon.com/lambda/latest/dg/golang-package.html)
- [C#](https://docs.aws.amazon.com/lambda/latest/dg/csharp-package.html)
- [Node.js](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-package.html)
- [PowerShell](https://docs.aws.amazon.com/lambda/latest/dg/powershell-package.html)
- [Java](https://docs.aws.amazon.com/lambda/latest/dg/java-package.html)
