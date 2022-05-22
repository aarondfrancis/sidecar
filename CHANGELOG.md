# Changelog
## Unreleased 

## 0.3.12 - 2022-05-22

### Changed

- Move `S3Client` to the container. by @lukeraymonddowning in https://github.com/hammerstonedev/sidecar/pull/72

### Fixed

- Fix for specifying a directory in a package when deploying from windows by @w00key
  in https://github.com/hammerstonedev/sidecar/pull/73

## 0.3.11 - 2022-05-12

### Added

- Add [Node 16 runtime](https://aws.amazon.com/blogs/compute/node-js-16-x-runtime-now-available-in-aws-lambda/) const


## 0.3.10 - 2022-05-11

### Added
- Add ECR permissions to deployment user by @bakerkretzmar in https://github.com/hammerstonedev/sidecar/pull/62
- Add ability to configure ephemeral storage size by @bakerkretzmar in https://github.com/hammerstonedev/sidecar/pull/71

### Fixed
- Replace DIRECTORY_SEPARATOR with '/' by @w00key in https://github.com/hammerstonedev/sidecar/pull/69
- Gracefully handle unexpected log output by @inxilpro in https://github.com/hammerstonedev/sidecar/pull/66

## 0.3.9

### Added

- Add new runtimes to README by @datashaman in https://github.com/hammerstonedev/sidecar/pull/59
- Add support for Guzzle v6 by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/58

## 0.3.8 - 2022-02-15

### Added

- Support for Laravel 9 in [#50](https://github.com/hammerstonedev/sidecar/pull/50)

## 0.3.7 - 2022-02-07

### Added

- Add yet another pending error string.
- Added `rawPromise` method to `PendingResult`.

## 0.3.6 - 2022-02-07

### Added 
- Ability to choose different architectures [https://github.com/hammerstonedev/sidecar/pull/42](https://github.com/hammerstonedev/sidecar/pull/42)
- Sidecar now creates an environment variable checksum to avoid publishing a new version when not required.
- Handlers now support an `@` sign to be more consistent with Laravel. `image@handler` is the same as `image.handler`
- If a payload is an instance of `Arrayable`, it will be cast to an array
- Package is now `macroable`
- New `Region` class full of consts

### Changed
- All 409 logic now lives in client middleware [https://github.com/hammerstonedev/sidecar/pull/47](https://github.com/hammerstonedev/sidecar/pull/47)
- `waitUntilFunctionUpdated` now accepts a string as well
- The signature of `Package::includeExactly` now includes a `followLinks` second param.
- `SettledResult::errorAsString` is public now 

### Removed
- ses, sqs, and dynamodb privileges were removed from the default execution role. This only affects new roles.

## 0.3.5 - 2022-01-09

### Added

- Add Package method to include strings as files.
- Add Package method to include files with more explicit path control ([#41](https://github.com/hammerstonedev/sidecar/pull/41))

### Fixed

- Wait for the function to update before updating environment variables.
- Be more clear when deleting keys

## 0.3.4 - 2022-01-02

### Fixed

- Add method to ensure function is updated before we try to do anything else. Should hopefully fix [#32](https://github.com/hammerstonedev/sidecar/issues/32)  


## 0.3.3 - 2021-11-01

### Added
- Added runtime constants ([#33](https://github.com/hammerstonedev/sidecar/pull/33))
- Add event invocation support ([#36](https://github.com/hammerstonedev/sidecar/pull/36))
 
### Fixed
- Docs typo ([#31](https://github.com/hammerstonedev/sidecar/pull/31)) 
- Update package documentation to include note for shipping node_modules ([#34](https://github.com/hammerstonedev/sidecar/pull/34))

## 0.3.2 - 2021-08-13

### Added
- Support for Container Images. [#29](https://github.com/hammerstonedev/sidecar/pull/29)

## 0.3.1 - 2021-07-31

### Fixed
- Cast Memory and Timeout to integers. Fixes [#28](https://github.com/hammerstonedev/sidecar/issues/28)

## 0.3.0 - 2021-07-20

### Added
- Support for Lambda environment variables ([#25](https://github.com/hammerstonedev/sidecar/pull/25))

## 0.2.0 - 2021-07-12 

### Added
- New `sidecar.env` config option to separate Sidecar environment from application environment. Useful mostly for teams who have multiple developers that all have an app env of `local` and don't want to be constantly overwriting each other's functions.
- New `sidecar:warm` command ([#6](https://github.com/hammerstonedev/sidecar/pull/6))
- Better error reporting when `sidecar:deploy` is called and there are no functions.
- Better error reporting when a function is not found. 
- Implemented sweeping to remove old, unused function versions ([#15](https://github.com/hammerstonedev/sidecar/pull/15))
- `--pre-warm` options to `sidecar:deploy` and `sidecar:active` commands ([Commit](https://github.com/hammerstonedev/sidecar/commit/4794e6d4bfc5ddb4976c4686939ca1ee0c0ae979))
- `latestVersionHasAlias` method to the LambdaClient ([Commit](https://github.com/hammerstonedev/sidecar/commit/a54f4e59aef9bfeac57ced7fb50b0c25ff268ab9))

### Changed
- Warming is now opt-in. 0 instances are configured by default. ([Commit](https://github.com/hammerstonedev/sidecar/commit/ba53467368bcb253034fdbae7726fb0916b28de2))
- Moved some methods into the Sidecar\LambdaClient ([#15](https://github.com/hammerstonedev/sidecar/pull/15))
- Break out logging & environment concerns from the Lambda Client. ([Commit](https://github.com/hammerstonedev/sidecar/commit/20e368c9773c4aae2262021c7682cf72737af270))

### Fixed
- Allow spacing in `APP_NAME` [#17](https://github.com/hammerstonedev/sidecar/pull/17)  

## 0.1.4 - 2021-06-05

- Added `*` option to include the entire base directory in the package. 

## 0.1.3 - 2021-05-24

- Fix more `sidecar:configure` AWS errors.

## 0.1.2 - 2021-05-24

- Fix other `sidecar:configure` AWS errors. ([#8](https://github.com/hammerstonedev/sidecar/issues/8) & ([#9](https://github.com/hammerstonedev/sidecar/issues/9))

## 0.1.1 - 2021-05-24

- Fix undefined `choice` ([#7](https://github.com/hammerstonedev/sidecar/issues/7))

##  v0.1.0 - May 15th, 2021.

First release.