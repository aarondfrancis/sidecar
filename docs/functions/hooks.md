# Function Hooks

There are a few ways to interact with your functions during the deployment and activation process.

Sidecar dispatches global [events](/events) as it's deploying and activating functions, and it also calls instance methods on each function.

The following four methods are available to you on every Sidecar function:

- `beforeDeployment`
- `afterDeployment`
- `beforeActivation`
- `afterActivation`

## Example: Before Deployment 

The `beforeDeployment` hook is a great place to run a build step if your function requires it. We'll be using the `ncc build` command mentioned in the [Handlers & Packages](handlers-and-packages#compiling-your-handler-with-ncc) section for this example.

In this example, we'll use Symfony's `Process` component to run the `ncc` command, so that we never forget to build our bundle before we deploy.

```php
use Symfony\Component\Process\Process;

class ExampleFunction extends LambdaFunction
{
    public function beforeDeployment()
    {
        Sidecar::log('Compiling bundle with NCC.');

        $command = ['ncc', 'build', 'resources/lambda/image.js', '-o', 'resources/lambda/dist'];

        Sidecar::log('Running `' . implode(' ', $command). '`');

        $process = new Process($command, $cwd = base_path(), $env = []);

        $process->setTimeout(60)->disableOutput()->mustRun();

        Sidecar::log('Bundle compiled!');
    }
}
```

With this in place, you'll see something like this in your logs:

```text
[Sidecar] Deploying App\Sidecar\Example to Lambda as `SC-App-local-Sidecar-Example`.
          ↳ Environment: local
          ↳ Runtime: nodejs14.x
          ↳ Compiling bundle with NCC.  [tl! focus]
          ↳ Running `ncc build resources/lambda/image.js -o resources/lambda/dist`  [tl! focus]
          ↳ Bundle compiled!  [tl! focus]
          ↳ Function already exists, potentially updating code and configuration.
          ↳ Packaging files for deployment.
          ↳ Creating a new zip file.
          ↳ Zip file created at s3://sidecar-us-east-2-XXXX/sidecar/001-7a2e86d9853b10c97b970af51d101f8d.zip
          ↳ Function code and configuration updated.
```