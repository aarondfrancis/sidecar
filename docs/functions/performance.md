
# Performance Tips

Here are some tips for getting the best performance out of your Sidecar functions.

## Reduce Cold Starts

Cold starts happen when Lambda spins up a new container to handle your request. To minimize their impact:

- **Use warming** - Configure `warmingConfig()` on your functions and schedule `sidecar:warm` to run regularly. See [Warming Functions](warming) for details.
- **Use pre-warming** - Add `--pre-warm` when activating to warm functions before they go live.
- **Keep functions warm** - Schedule `sidecar:warm` to run every 5 minutes to prevent containers from being frozen.

## Optimize Your Package Size

Smaller packages deploy faster and have quicker cold starts:

- **Separate your node_modules** - Keep Lambda dependencies in a separate `package.json` from your main app.
- **Use NCC** - Compile Node.js handlers into a single file with [NCC](https://github.com/vercel/ncc). See [Handlers & Packages](handlers-and-packages#compiling-your-handler-with-ncc).
- **Only include what you need** - Be specific about what goes in your `package()` method.

## Right-Size Memory

Lambda allocates CPU proportionally to memory. More memory means more CPU:

- **Profile your functions** - Use `$result->report()` to see memory usage and execution time.
- **Test different memory settings** - Sometimes paying for more memory results in faster execution and lower total cost.

## Use Async When Possible

If you don't need the result immediately:

- **Use `executeAsync()`** - Let your code continue while Lambda runs in the background.
- **Use `executeMany()`** - Run multiple invocations in parallel instead of sequentially.
- **Use `executeAsEvent()`** - For fire-and-forget scenarios where you don't need the response.

