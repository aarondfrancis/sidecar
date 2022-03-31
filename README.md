# Sidecar for Laravel

[![Tests](https://github.com/hammerstonedev/sidecar/actions/workflows/tests.yml/badge.svg)](https://github.com/hammerstonedev/sidecar/actions/workflows/tests.yml) [![Latest Stable Version](https://poser.pugx.org/hammerstone/sidecar/v)](//packagist.org/packages/hammerstone/sidecar) [![Total Downloads](https://poser.pugx.org/hammerstone/sidecar/downloads)](//packagist.org/packages/hammerstone/sidecar) [![License](https://poser.pugx.org/hammerstone/sidecar/license)](//packagist.org/packages/hammerstone/sidecar)

### Deploy and execute AWS Lambda functions from your Laravel application.

> Read the full docs at [hammerstone.dev/sidecar/docs](https://hammerstone.dev/sidecar/docs/main/overview).

Follow me on Twitter for more updates: [twitter.com/aarondfrancis](https://twitter.com/aarondfrancis)

To install, simply require the package from composer: `composer require hammerstone/sidecar`

This package is still under development, please open issues for anything you run into.

## What Sidecar Does

Sidecar packages, creates, deploys, and executes Lambda functions from your Laravel application. 

You can write functions in any of the following runtimes and execute them straight from PHP:

- Node.js 14
- Node.js 12
- Node.js 10
- Python 3.9
- Python 3.8
- Python 3.7
- Python 3.6
- Python 2.7
- Ruby 2.7
- Ruby 2.5
- Java 11
- Java 8
- Go 1.x
- .NET 6
- .NET Core 3.1
- .NET Core 2.1 

Any runtime that [Lambda supports](https://docs.aws.amazon.com/lambda/latest/dg/lambda-runtimes.html), you can use!

### [Hammerstone](https://www.hammerstone.dev)

Sidecar is maintained by [Aaron Francis](https://twitter.com/aarondfrancis). If you find it useful consider checking out [Refine](https://hammerstone.dev/refine/laravel/docs/main) which allows your users to easily create filters you can run on your app’s data. Works with Laravel and Vue 2 or Vue 3.

### What It Looks Like

Every Sidecar Function requires two things:

- A PHP Class
- Files that you want deployed to Lambda

For example, if we were wanting to use Node on Lambda to generate an og:image for all of our blog posts, we would first set up a simple class in PHP called `OgImage`.

`App\Sidecar\OgImage.php`

```php
namespace App\Sidecar;

use Hammerstone\Sidecar\LambdaFunction;

class OgImage extends LambdaFunction
{
    public function handler()
    {
        // Define your handler function. 
        return 'lambda/image.handler';
    }

    public function package()
    {
        // All files and folders needed for the function.
        return [
            'lambda',
        ];
    }
}
```

That's it! There are a lot more options, but that's all that is required.

The second thing you'd need is your function's "handler", in this case a javascript file.

Here's a simple JS file that could serve as our handler:

`resources/lambda/image.js`

```js
const {createCanvas} = require('canvas')

exports.handler = async function (event) {
    const canvas = createCanvas(1200, 630)
    const context = canvas.getContext('2d')

    context.font = 'bold 70pt Helvetica'
    context.textAlign = 'center'
    context.fillStyle = '#3574d4'

    // Read the text out of the event passed in from PHP.
    context.fillText(event.text, 600, 170);
    
    // Return an image.
    return canvas.toDataURL('image/jpeg');
}
```

With those files created, you can deploy this function to Lambda:

```
php artisan sidecar:deploy --activate
```

And then execute it straight from your Laravel app!

`web.php`

```php
Route::get('/ogimage', function () {
    return OgImage::execute([
        'text' => 'PHP to JS and Back Again!'
    ]);
});
```

Sidecar passes the payload from `execute` over to your Javascript function. Your Javascript function generates an image and sends it back to PHP.

Sidecar reduces the complexity of deploying small bits of code to Lambda. 


### Why Sidecar Exists

[AWS Lambda](https://aws.amazon.com/lambda/) is a powerful service that allows you to run code without provisioning or thinking about servers.

[Laravel Vapor](https://vapor.laravel.com/) brought that power to Laravel. Using Vapor, you can run your plain ol' Laravel apps on a serverless platform and get incredible speed, security, and reliability.

Using Lambda through Vapor is a wonderful developer experience, but there are times when building your applications that you need to run just _one or two_ Node functions for some reason. Common use cases could be taking screenshots with headless Chrome, generating images, or doing server-side rendering of your Javascript frontend. 

Or maybe you want to run a Python script without configuring a server? Or a single Ruby script. Or even Java!

When running on a serverless platform, it's not quite as easy as installing Node and running your functions. You don't have access to the server! So you end up deploying a single Vercel or Netlify function and calling it over HTTP or just forgetting the thing altogether.

Sidecar brings the ease of Vapor to those non-PHP functions. 

## What Sidecar Doesn't Do

Sidecar does _not_ handle any API Gateway, Databases, Caches, etc. The _only_ thing Sidecar concerns itself with is packaging, creating, deploying, and executing Lambda functions.

Sidecar does not provide a way to execute a function via HTTP. You must execute it from your Laravel app through the provided methods.  

If you need those other services, you are encouraged to use the instances that Vapor has set up for you, or set them up yourself. 
