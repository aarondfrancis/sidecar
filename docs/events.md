
# Events

Sidecar fires a few events related to deployment that you can hook into:

- `BeforeFunctionsDeployed`
- `AfterFunctionsDeployed`
- `BeforeFunctionsActivated`
- `AfterFunctionsActivated`

Each of these events has a public `functions` property that holds all the functions that are being deployed or activated.

You can use these events to build packages, install dependencies, or clean up after they are deployed.