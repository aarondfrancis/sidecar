
# Warming Functions

The first request that comes in for every newly activated function will incur a "startup penalty" known as a "cold start." Cold starts are an AWS phenomenon whereby AWS is spinning up your container for the first time to handle the request. This process can take up to a second or two depending on the runtime.

If you function goes for some time without being called then Lambda will "freeze" it to save resources. When a new request comes in it will "unfreeze" that container for reuse. There is no telling when a container will be frozen, or when Lambda will decide to completely throw away a container and start a new one from scratch.

[Much has been written](https://www.google.com/search?q=aws+lambda+cold+start) about cold starts.

To help mitigate the effect of cold starts, Sidecar provides you with a warming mechanism to keep a certain number of containers booted and ready to go.

## Warming Configuration

Sidecar ships with a `WarmingConfig` class to help you configure your desired warming configuration. By default every function returns an empty `WarmingConfig`, meaning that warming is disabled. 

```php
class ExampleFunction extends LambdaFunction 
{
    /**
     * A warming configuration that can help mitigate against
     * the Lambda "Cold Boot" problem.
     *
     * @return WarmingConfig
     */
    public function warmingConfig() // [tl! focus:3]
    {
        return new WarmingConfig;
    }
}
```

### Instances 

To enable warming, you must configure the number of instances you want warmed. 

Every Lambda can only handle one request at a time. If multiple requests come in at the _exact_ same time, AWS will spin up new containers to handle all of those requests. The number of instances you decide to warm depends on how busy you expect your function to be. You may have to play around with it!

If you wanted to warm 5 containers, you could call the static `instances` method on the `WarmingConfig`, or pass it in through the constructor. 

```php
public function warmingConfig()
{
   // Using the static `instances`.
   return WarmingConfig::instances(5);
}
```

```php
public function warmingConfig()
{
   // Using the constructor.
   return new WarmingConfig(5);
}
```

This instructs Sidecar that when it is time to warm, 5 requests should be sent simultaneously so that Lambda boots 5 containers.

### Payload

Sidecar will send the number of request configured by the `instances` variable. Sidecar will also send along the payload defined by your warming configuration. By default, this config is just an array with a single `warming` key:

```php
[
    'warming' => true
]
```

You can then look for this payload in your handler and bail out early if you see it:

handler.js {.filename}
```js
exports.handle = async function(event) {
    // Bail early if it's a warming event.
    if (event.warming) {
        return;
    }
    
    // Actual handling code...
}
```

If you'd like to change the payload, you can update it via the `payload` method on the `WarmingConfig` class:

```php
public function warmingConfig()
{
   return WarmingConfig::instances(5)->payload([
       'foo' => 'bar'
   ]);
}
```

Sidecar will now send along your custom payload instead of the default one.

## Warming Command

To keep your containers warm after they have been deployed, you may add the `sidecar:warm` command to your Laravel schedule:

Kernel.php {.filename}
```php
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sidecar:warm')->everyFiveMinutes(); // [tl! focus]
    }
}
```

## Warming Facade

If you want to warm your functions manually, you may use the facade: `Sidecar::warm()`.

## Pre-warming

You may use the command above to keep your functions after they are deployed, but Sidecar also gives you the ability to warm your functions _before they are activated_.

When you are activating your functions, you can add the `--pre-warm` flag to instruct Sidecar to warm the functions before they are switched live. This means when they are activated, they'll be booted and ready to go!

```shell
# Pre-warm with the `deploy` command
php artisan sidecar:deploy --activate --pre-warm

# Or pre-warm with the `activate` command
php artisan sidecar:activate --pre-warm 
```

To read more about deploying and activating, take a look at their corresponding pages:

- [Deploying](deploying)
- [Activating](activating)

For more tips on how to use warming to further speed up your invocations, take a look at the [Performance Tips](performance) page
