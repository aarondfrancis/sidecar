
# Executing Functions

Executing your Sidecar functions is as easy as calling `execute` on your function classes or on the Sidecar facade.

```php
// Using the function directly
$result = OgImage::execute();

// Using the facade
$result = Sidecar::execute(OgImage::class);
```

## Passing Data to Lambda

Most of your functions are going to require some input to operate properly. Anything you pass to `execute` will be passed on to your Lambda function.

```php
$result = OgImage::execute([
    'title' => 'Executing Functions',   
    'url' => 'https://hammerstone.dev/sidecar/docs/main/functions/executing',
    'template' => 'docs/sidecar'
]);
```

The entire payload will be available for use in your Lambda function now:

image.js {.filename}
```js
exports.handler = async function (event) {
    console.log(event);
    // {
    //     title: 'Executing Functions',   
    //     url: 'https://hammerstone.dev/sidecar/docs/main/functions/executing',
    //     template: 'docs/sidecar'
    // }
}
```

## Sync vs. Async vs. Event

By default, all executions of Sidecar functions are synchronous, meaning script execution will stop while the Lambda finishes and returns its result. This is the simplest method and probably fine for the majority of use cases. 

```php
// Synchronous execution. 
$result = OgImage::execute();

echo 'Image has been fully created and returned!';
```

If you'd like for the execution to be _asynchronous_, meaning the rest of your script will carry on without waiting, you can use the `executeAsync` method, or pass a second param of `true` to the `execute` method.

```php
// Async execution using the class. 
$result = OgImage::executeAsync();
$result = OgImage::execute($payload = [], $async = true);

// Async execution using the facade.
$result = Sidecar::executeAsync(OgImage::class);
$result = Sidecar::execute(OgImage::class, $payload = [], $async = true);

echo 'Image may or may not have finished generating yet!';
```

Whilst the execution is asynchronous, it is expected that you wait for the response, which is documented more in the next section below. If you're looking for "fire-and-forget" style execution, where you don't care about the response and are happy for execution to occur in the background then you'll need to execute your function as an event.

```php
// Event execution using the class. 
$result = OgImage::executeAsEvent();
$result = OgImage::execute($payload = [], $async = false, $invocationType = 'Event');

// Event execution using the facade.
$result = Sidecar::executeAsEvent(OgImage::class);
$result = Sidecar::execute(OgImage::class, $payload = [], $async = false, $invocationType = 'Event');

echo 'Image may or may not have finished generating yet!';
```

### Settled Results

When your function is executed using one of the sync methods, the return value will be an instance of `SettledResult`. The Settled Result class is responsible for delivering the result of your Lambda, along with the logs and information about duration, memory used, etc.

You can read more about that in the [body](#result-body) and [logs & timing](#logs--timing) sections below. 

### Pending Results

If your function is invoked using one of the async methods, the return value will be an instance of `PendingResult`. This class is a thin wrapper around a Guzzle promise that represents your pending function execution.

Given a Pending Result, if you'd like to pause execution until the promise is settled, you can call `settled`. This will return a `SettledResult`.

```php
$result = OgImage::executeAsync();

// Do some other stuff while the Lambda executes...
// ...
// ...

// Halt execution now while we wait for the Lambda
// execution to finish. (It may already be done!)
$result = $result->settled();

// $result is now a SettledResult.
dump($result instanceof SettledResult);
// true
```

Using the async methods is powered by Guzzle promises. Given the limitations of the Guzzle async implementation, as it stands today, you need to wait for the response to ensure all your requests have been made.

### Working With Either

If you're not sure whether a given result is a Settled Result or a Pending Result, you can _always_ called `settled`.

- When you call `settled` on a Settled Result, it will just return itself.
- When you call `settled` on a Pending Result, it will wait, and then return the Settled Result. 
- When you call `settled` on a Pending Result that has already settled, it will return the same Settled Result it returned the first time!

```php
// Create a Pending Result
$pending = OgImage::executeAsync();

// Settle a Pending Result
$result = $pending->settled();

// Only one Settled Result per Pending result, 
// so you can call it over and over again!
$pending->settled() === $pending->settled();
// > true

// Settle a Settled Result. No harm done!
$result = OgImage::execute()->settled();

// Settle a Settled Result over and over.
// Silly, but not bad!
$result = OgImage::execute()->settled()->settled()->settled()->settled();
```

### Customizing the Results

If you want more control over the process of creating result classes, you can override the `toResult` class in your `LambdaFunction`. That method receives either an `Aws\Result`, or a Guzzle Promise, depending on whether the request was sync or async. 

You may also override the `toSettledResult` or `toPendingResult` methods:

Image.php {.filename}
```php
class OgImage extends LambdaFunction 
{
    public function handler() // [tl! collapse:start]
    {
        // 
    } // [tl! collapse:end]

    public function package() // [tl! collapse:start]
    {
        //
    } // [tl! collapse:end]

    public function toSettledResult(Result $raw) // [tl! focus:4]
    {
        // Use a custom settled result class for this function.
        return new OgImageResult($raw, $this);
    }
}
```

## Result Body

Your Lambda function will likely return some data to be consumed by your Laravel application. You can retrieve this data by calling the `body` method on the `SettledResult` class.

In the case of generating an image, the response might be the image itself:

image.js {.filename}
```js
exports.handler = async function (event) {
    const canvas = createCanvas(1200, 630)
    // [tl! collapse:start]
    const context = canvas.getContext('2d')

    context.font = 'bold 70pt Helvetica'
    context.textAlign = 'center'
    context.fillStyle = '#3574d4'
    context.fillText(text, 600, 170)
    // [tl! collapse:end]
    // Return an image. 
    return canvas.toDataURL('image/jpeg');
}
```

```php
echo OgImage::execute()->body();

// data:image/jpeg;base64,/9j/4AA[.....]cU+ThI/wBH/9k=
```

If your function returns a JSON object, you can access that via the `body` as well.

foo.js {.filename}
```js
exports.handler = async function (event) {
    return {
        foo: 'bar'
    }
}
```

```php
echo FooFunction::execute()->body()['foo'];

// bar
```

If you'd like to control how the body is decoded, you can pass any JSON options to the `body` method. 

Because the default is `JSON_OBJECT_AS_ARRAY`, to decode your JSON into an object, you could simply pass `null`.

```php
echo FooFunction::execute()->body($options = null)->foo;

// bar
```

## Logs & Timing

To retrieve the logs from your function execution, you can call `logs` on the `SettledResult` class. Everything that is logged from your function will be returned for your inspection.

foo.js {.filename}
```js
exports.handler = async function (event) {
    console.log('Hi from Lambda!');

    return {
        foo: 'bar'
    }
}
```

```php
FooFunction::execute()->logs();

// [
//    [
//     "timestamp" => 1619990695
//     "level" => "INFO"
//     "body" => "Hi from Lambda!"
//   ]
// ]
```

To see a report on the timing and memory usage of your function, call the `report` method.

```php
FooFunction::execute()->report();

// [
//   "request" => "75d3e393-f4ab-4528-a8d3-ee5c41c470c7"
//   "billed_duration" => 2
//   "execution_duration" => 1.06
//   "cold_boot_delay" => 0
//   "total_duration" => 1.06
//   "max_memory" => 66
//   "memory" => 512
// ]
```

## HTTP Responses

If you want to directly return a result as a proper HTTP response, you may override the `toResponse` method on your function.

By default, the `toResponse` function returns the body or your result:

```php
abstract class LambdaFunction 
{
    public function toResponse($request, SettledResult $result)
    {
        // If the Lambda failed, throw an exception.
        $result->throw();
        
        // Otherwise return the response we got from Lambda.
        return response($result->body());
    }
}
```

This allows you to return directly from your controllers or routes:

web.php {.filename}
```php
Route::get('/ogimage', function (Request $request) {
    return OgImage::execute($request->query());
});
``` 

In the case of our image, we'll want to customize the response a little bit so that the browser will render an image instead of a string of text. We can do this by customizing the `toResponse` method:


App\Sidecar\OgImage.php {.filename}
```php
class OgImage extends LambdaFunction
{
    public function handler() // [tl! collapse:start closed]
    {
        // Define your handler function. 
        // (Javascript file + export name.) 
        return 'lambda/image.handler';
    } // [tl! collapse:end]

    public function package() // [tl! collapse:start closed]
    {
        // All files and folders needed for the function.
        return [
            'lambda',
        ];
    } // [tl! collapse:end]

    public function toResponse($request, SettledResult $result) // [tl! focus:9]
    {
        // Throw an exception if it failed.
        $result->throw();

        $image = base64_decode($result->body());
        
        // Set an appropriate header.
        return response($image)->header('Content-type', 'image/jpg');
    }
}
```

Now we can create images on Lambda and return them directly to the browser, and they will render as images!

## Executing Multiple

To execute multiple functions _at the same time_, you can use the `executeMany` method on your `LambdaFunction`.

### With No Payload

If you want to execute the function 5 times, with no payload, you can just pass in the integer `5`.

```php
// $results will be an array of SettledResults
$results = OgImage::executeMany(5);
```

This will return an array full of `SettledResults`.

### With Payloads

More likely, you'll need to execute multiple functions with distinct payloads, in which case you can pass them as the first param.

```php
// $results will be an array of SettledResults
$results = OgImage::executeMany([[
    'text' => 'Creating Functions'
], [
    'text' => 'Deploying Functions'
], [
    'text' => 'Executing Functions'
]]);
```

### Without Waiting

By default the executions are all run in parallel, but then Sidecar waits until they are _all settled_ to return anything.
 
To execute many functions without waiting for anything, pass `true` as the second parameter. This will return an array full of `PendingResults` to you.


```php
// $results will be an array of PendingResults
$results = OgImage::executeMany([[
    'text' => 'Creating Functions'
], [
    'text' => 'Deploying Functions'
], [
    'text' => 'Executing Functions'
]], $async = true);
```

You can also call `executeManyAsync`:

```php
// $results will be an array of PendingResults
$results = OgImage::executeManyAsync([[
    'text' => 'Creating Functions'
], [
    'text' => 'Deploying Functions'
], [
    'text' => 'Executing Functions'
]]);
```

## Execution Exceptions

If your Lambda throws an exception or otherwise errors out, you'll need to be able to act on that back in your Laravel application.

We'll take a very basic example of a Node function that simply throws an error:

errors.js {.filename}
```js
exports.handler = async function (event) {
    throw new Error('Error from Lambda!');
}
```

When executing that function from PHP, Sidecar will not throw an exception unless explicitly asked to.
 
```php
// Execute synchronously. No error thrown yet.
$result = ErrorFunction::execute();

// When asked to, Sidecar will throw a PHP Exception 
// if there was a runtime error on the Lambda side.
$result->throw();

// > Hammerstone\Sidecar\Exceptions\LambdaExecutionException
// > Lambda Execution Exception for App\Sidecar\FooFunction: "Error from Lambda!. 
// > [TRACE] Error: Error from Lambda! 
// > at Runtime.exports.handler (/var/task/lambda/error.js:2:11) 
// > at Runtime.handleOnce (/var/runtime/Runtime.js:66:25)".
```

When you call `throw`, Sidecar will throw a `LambdaExecutionException` _if there is one_. If there isn't, nothing will happen.

```php
// Execute synchronously.
$result = NonErrorFunction::execute();

// No error? No problem. Call it just in case.
return $result->throw()->body();
```

If you don't want to throw an exception, but want to handle it in another way, you may check the `isError` method.

```php
// Execute synchronously. No error thrown yet.
$result = ErrorFunction::execute();

if ($result->isError()) { // [tl! ~~]
    // Do something, anything!
}
```

Sidecar also provides the `trace` to you:

```php
// Execute synchronously. Nothing will happen.
$result = ErrorFunction::execute();

// Dump the error trace.
dd($result->trace()); // [tl! ~~]

// [
//   "Error: Error from Lambda!"
//   "    at Runtime.exports.handler (/var/task/lambda/error.js:2:11)"
//   "    at Runtime.handleOnce (/var/runtime/Runtime.js:66:25)"
// ]
```

