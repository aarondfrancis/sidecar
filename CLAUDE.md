# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is Sidecar?

Sidecar is a Laravel package that lets you deploy and run AWS Lambda functions directly from your Laravel app. Write a function in Node, Python, Ruby, Java, or any Lambda-supported runtime, and call it from PHP as easily as `MyFunction::execute(['key' => 'value'])`.

The package handles all the AWS complexity: packaging your code, uploading to S3, creating/updating Lambda functions, managing versions, and invoking them.

## Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/Unit/FunctionTest.php

# Run a specific test method
./vendor/bin/phpunit --filter test_runtime_value_resolves_enum_to_string
```

Tests use Orchestra Testbench. The test suite has unit tests in `tests/Unit/` and integration tests in `tests/Integration/`. Most development work uses unit tests with mocked AWS clients.

## How the Code is Organized

### The Main Players

**`LambdaFunction`** is the abstract base class that users extend. Every function needs two methods:
- `handler()` - tells Lambda which file/function to run (e.g., `'image.handler'`)
- `package()` - lists files to include in the deployment ZIP

Users call static methods like `MyFunction::execute($payload)` to run their functions.

**`Manager`** (accessed via the `Sidecar` facade) does the actual work of invoking Lambda functions. It prepares payloads, calls the AWS SDK, and wraps responses in result objects.

**`Deployment`** handles the deploy workflow: package the code, create or update the Lambda function, then optionally activate it by pointing an alias to the new version.

**`Package`** builds the ZIP file for deployment. It collects files based on include/exclude patterns, computes a hash for change detection, and streams the ZIP directly to S3.

### AWS Clients

The `src/Clients/` directory has thin wrappers around AWS SDK clients:
- `LambdaClient` extends the SDK client and adds retry logic for Lambda's "Pending" state
- `S3Client` and `CloudWatchLogsClient` are simpler wrappers

### Results

When you execute a function:
- Sync calls return a `SettledResult` with the response body, logs, and error info
- Async calls return a `PendingResult` that resolves to `SettledResult` when you call `->settled()`

### Runtime and Architecture

`Runtime` and `Architecture` are PHP 8.1 backed enums. There are also deprecated `RuntimeConstants` and `ArchitectureConstants` classes for backwards compatibility.

When working with these, use `runtimeValue()` and `architectureValue()` methods to get the string values - they handle both enum and string inputs.

## Configuration

Everything lives in `config/sidecar.php`:
- `functions` - array of `LambdaFunction` classes to deploy
- `env` - environment name, used to namespace function names (defaults to `APP_ENV`)
- `timeout`, `memory`, `storage` - default Lambda settings
- AWS credentials (`aws_key`, `aws_secret`, `aws_region`, `aws_bucket`, `execution_role`)

Function names are automatically prefixed with app name and environment to avoid collisions.

## Artisan Commands

These are what users run (not needed for package development, but good to know):
- `sidecar:deploy` - packages and deploys functions to Lambda
- `sidecar:activate` - points the "active" alias to latest version
- `sidecar:deploy --activate` - both in one step
- `sidecar:warm` - pre-warms function instances to reduce cold starts
- `sidecar:configure` - interactive wizard to set up AWS resources

## Events

The package fires Laravel events you can hook into:
- `BeforeFunctionsDeployed` / `AfterFunctionsDeployed`
- `BeforeFunctionsActivated` / `AfterFunctionsActivated`
- `BeforeFunctionExecuted` / `AfterFunctionExecuted`

## Things to Know

- This package supports Laravel 10, 11, and 12 with PHP 8.1+
- All source files use `declare(strict_types=1)`
- The codebase uses constructor property promotion and typed properties throughout
- Tests use Mockery for mocking AWS clients - check existing tests for patterns
