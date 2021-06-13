# Changelog

## Unreleased 

- Added `sidecar.env` config option to separate Sidecar environment from application environment. Useful mostly for teams who have multiple developers that all have an app env of `local` and don't want to be constantly overwriting each other's functions.
- Added better error reporting when `sidecar:deploy` is called and there are no functions.
- Added better error reporting when a function is not found. 
- Added `sidecar:warm` function
- Added sweeping to remove old, unused functions
- Changed: moved some methods into the Sidecar\LambdaClient  

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