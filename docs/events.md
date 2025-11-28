
# Events

Sidecar fires events during deployment, activation, and execution that you can hook into.

## Deployment & Activation Events

- `BeforeFunctionsDeployed`
- `AfterFunctionsDeployed`
- `BeforeFunctionsActivated`
- `AfterFunctionsActivated`

Each of these events has a public `functions` property that holds all the functions being deployed or activated. You can use these to build packages, install dependencies, or clean up after deployment.

## Execution Events

- `BeforeFunctionExecuted`
- `AfterFunctionExecuted`

These events fire every time a function is executed. `BeforeFunctionExecuted` has `function` and `payload` properties. `AfterFunctionExecuted` adds the `result` property.

You can use these for logging, monitoring, or modifying payloads before they're sent to Lambda.