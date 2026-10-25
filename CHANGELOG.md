# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/aarondfrancis/sidecar/compare/v0.8.0...HEAD)

<!-- New Release notes will be placed here automatically -->
## [v0.8.0](https://github.com/aarondfrancis/sidecar/compare/v0.7.1...v0.8.0) - 2026-05-31

### What's Changed

* Laravel 13.x Compatibility by @laravel-shift in https://github.com/aarondfrancis/sidecar/pull/167
* Update Runtime and add deprecations by @stefanzweifel in https://github.com/aarondfrancis/sidecar/pull/170

## [v0.7.1](https://github.com/aarondfrancis/sidecar/compare/v0.7.0...v0.7.1) - 2025-08-22

### What's Changed

* SettledResult isError fix by @livingos in https://github.com/aarondfrancis/sidecar/pull/162
* Add new runtime environments by @owenvoke in https://github.com/aarondfrancis/sidecar/pull/160
* Add default value for `$numberOfBacktraces` in `SettledResult::throw` by @inxilpro in https://github.com/aarondfrancis/sidecar/pull/149

### New Contributors

* @livingos made their first contribution in https://github.com/aarondfrancis/sidecar/pull/162
* @owenvoke made their first contribution in https://github.com/aarondfrancis/sidecar/pull/160

## [v0.7.0](https://github.com/aarondfrancis/sidecar/compare/v0.6.1...v0.7.0) - 2025-05-07

### What's Changed

* Laravel 12.x Compatibility by @laravel-shift in https://github.com/aarondfrancis/sidecar/pull/156
* Make the description dependent on the deployment environment by @Akecel in https://github.com/aarondfrancis/sidecar/pull/153
* Deprecate .NET 7, Node.js 16 and Python 3.8 runtime options by @wilsenhc in https://github.com/aarondfrancis/sidecar/pull/146

### New Contributors

* @laravel-shift made their first contribution in https://github.com/aarondfrancis/sidecar/pull/156
* @Akecel made their first contribution in https://github.com/aarondfrancis/sidecar/pull/153

## [v0.6.1](https://github.com/aarondfrancis/sidecar/compare/v0.6.0...v0.6.1) - 2024-05-15

### What's Changed

* Add .NET 8 and Ruby 3.3 runtime options by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/145

## [v0.6.0](https://github.com/aarondfrancis/sidecar/compare/v0.5.0...v0.6.0) - 2024-05-15

### 🚨 Breaking change!

The default runtime for functions has been changed from Node 14 to Node 20. If you haven't explicitly declared the runtime in your function, this will change it to Node 20!

### What's Changed

* Update for Laravel 11, PHP8 and ZipStream 3.1 by @drjdr in https://github.com/hammerstonedev/sidecar/pull/139
* Config steps update - "Documentation" by @bookwyrm in https://github.com/hammerstonedev/sidecar/pull/137
* Added option to configure number of backtraces in error message by @Froelund in https://github.com/hammerstonedev/sidecar/pull/131

### New Contributors

* @drjdr made their first contribution in https://github.com/hammerstonedev/sidecar/pull/139
* @bookwyrm made their first contribution in https://github.com/hammerstonedev/sidecar/pull/137
* @Froelund made their first contribution in https://github.com/hammerstonedev/sidecar/pull/131

## [v0.5.0](https://github.com/aarondfrancis/sidecar/compare/v0.4.2...v0.5.0) - 2024-03-20

### What's Changed

* Add Support for Laravel 11 by @stefanzweifel in https://github.com/hammerstonedev/sidecar/pull/141
* Dropped tests for Laravel 8

## [v0.4.2](https://github.com/aarondfrancis/sidecar/compare/v0.4.1...v0.4.2) - 2024-01-10

### What's Changed

* Update supported Runtimes by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/123

## [v0.4.1](https://github.com/aarondfrancis/sidecar/compare/v0.4.0...v0.4.1) - 2023-04-29

### What's Changed

* Documents command options by @joedixon in https://github.com/hammerstonedev/sidecar/pull/106
* AWS New users experience update by @tominal in https://github.com/hammerstonedev/sidecar/pull/100
* Make warming actually work by @aarondfrancis in https://github.com/hammerstonedev/sidecar/pull/110

### New Contributors

* @joedixon made their first contribution in https://github.com/hammerstonedev/sidecar/pull/106
* @tominal made their first contribution in https://github.com/hammerstonedev/sidecar/pull/100

## [v0.4.0](https://github.com/aarondfrancis/sidecar/compare/v0.3.12...v0.4.0) - 2023-02-28

### 🚨 This is a breaking release

🚨 Function naming algorithm was changed to support long prefixes, long function names, and previously invalid characters. This is a breaking change because the names of your current functions _might_ change with using the new naming scheme. After upgrading to Sidecar 0.4.0, it _might_ deploy a new version of your function even if nothing has changed, because of the new naming scheme. See more in [#30](https://github.com/hammerstonedev/sidecar/pull/30) and [#60](https://github.com/hammerstonedev/sidecar/pull/60).

🚨 Support for Laravel 7 and PHP 7.2 have been dropped.

🚨 Unsupported Lambda runtimes have been removed. See [#90](https://github.com/hammerstonedev/sidecar/pull/90) for more.

### Full list of what's Changed

* Update test matrices by @aarondfrancis in https://github.com/hammerstonedev/sidecar/pull/77
* Add Node.js 16.x runtime to all docs by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/82
* Improve LambdaFunction naming and prefix convention by @maurocasas in https://github.com/hammerstonedev/sidecar/pull/30
* Adding Laracasts reference by @binaryk in https://github.com/hammerstonedev/sidecar/pull/80
* Add `sidecar.app_name` config variable by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/83
* Timeout is specified in seconds by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/81
* Add Support for Laravel 10 by @stefanzweifel in https://github.com/hammerstonedev/sidecar/pull/101
* Add support for PHP 8.2 by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/98
* BREAKING CHANGE: Removing unsupported runtimes by @nexxai in https://github.com/hammerstonedev/sidecar/pull/90
* Allow tagging functions by @wilsenhc in https://github.com/hammerstonedev/sidecar/pull/86

### New Contributors

* @maurocasas made their first contribution in https://github.com/hammerstonedev/sidecar/pull/30
* @binaryk made their first contribution in https://github.com/hammerstonedev/sidecar/pull/80
* @stefanzweifel made their first contribution in https://github.com/hammerstonedev/sidecar/pull/101
* @nexxai made their first contribution in https://github.com/hammerstonedev/sidecar/pull/90

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