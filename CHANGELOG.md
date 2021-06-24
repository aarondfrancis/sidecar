# Changelog

## Unreleased 

### Added
- New `sidecar.env` config option to separate Sidecar environment from application environment. Useful mostly for teams who have multiple developers that all have an app env of `local` and don't want to be constantly overwriting each other's functions.
- New `sidecar:warm` command ([#6](https://github.com/hammerstonedev/sidecar/pull/6))
- Better error reporting when `sidecar:deploy` is called and there are no functions.
- Better error reporting when a function is not found. 
- Implemented Sweeping to remove old, unused function versions ([#15](https://github.com/hammerstonedev/sidecar/pull/15))
- `--pre-warm` options to `sidecar:deploy` and `sidecar:active` commands ([Commit](https://github.com/hammerstonedev/sidecar/commit/4794e6d4bfc5ddb4976c4686939ca1ee0c0ae979))
- `latestVersionHasAlias` method to the LambdaClient ([Commit](https://github.com/hammerstonedev/sidecar/commit/a54f4e59aef9bfeac57ced7fb50b0c25ff268ab9))

### Changed
- Warming is now opt-in. 0 instances are configured by default. ([Commit](https://github.com/hammerstonedev/sidecar/commit/ba53467368bcb253034fdbae7726fb0916b28de2))
- Moved some methods into the Sidecar\LambdaClient ([#15](https://github.com/hammerstonedev/sidecar/pull/15))
- Break out logging & environment concerns from the Labmda Client. ([Commit](https://github.com/hammerstonedev/sidecar/commit/20e368c9773c4aae2262021c7682cf72737af270))

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