# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][keepachangelog] and this project adheres to [Semantic Versioning][semver].

## v3.5.0

### Added

- Listener `EnableHttpMethodParameterOverrideListener` for forced support of `_method` request parameter (for determining the intended HTTP method) [#9]

### Changed

- Listener `EnableHttpMethodParameterOverrideListener` is enabled by default in the configuration file [#9]

### Fixed

- Sending any form data with a `DELETE` or `PUT` method (the application ignored the hidden field `_method` and as a result the action necessary for the form did not occur) [#9]

[#9]:https://github.com/spiral/roadrunner-laravel/pull/9

## v3.4.0

### Added

- Source code style checking using `spiral/code-style` package [#3]

### Changed

- Minimal required PHP version now is `7.2` [#3]

[#3]:https://github.com/spiral/roadrunner-laravel/issues/3

## v3.3.0

### Added

- Event `LoopErrorOccurredEvent` (triggered on request processing exception)
- Listener `SendExceptionToStderrListener` for direct exception sending (as a string) into `stderr`
- Listener `StopWorkerListener` for worker stopping

### Changed

- Default package configuration includes `LoopErrorOccurredEvent` event listeners: `SendExceptionToStderrListener` and `StopWorkerListener` [#42]
- When "debug mode" (`app.debug`) is **not** enabled - client will get only `Internal server error` string instead exception with stacktrace [#42]

### Fixed

- Double response sending on request processing error (calling `$psr7_client->respond` and `$psr7_client->getWorker()->error` after that)

[#42]:https://github.com/avto-dev/roadrunner-laravel/issues/42

[keepachangelog]:https://keepachangelog.com/en/1.0.0/
[semver]:https://semver.org/spec/v2.0.0.html
