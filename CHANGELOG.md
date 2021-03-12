# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][keepachangelog] and this project adheres to [Semantic Versioning][semver].

## UNRELEASED (v4 candidate)

### Added

- Package "binary" file allows using next options:
  - `laravel-path` for Laravel application base path changing
  - `relay-dsn` for RR relay changing (you can set `tcp://localhost:6001`, `unix:///tmp/rpc.sock` and others; `pipes` is used by default)
  - `refresh-app` for application instance refreshing on each incoming HTTP request (instead `APP_REFRESH` env variable)
- Possibility to use unix socket or TCP port as a relay to communicate with RoadRunner
- `Spiral\RoadRunnerLaravel\WorkerOptionsInterface` that describes worker starting options
- Feature tests (`phpunit`) that uses real RR server running

### Changed

- Minimal required PHP version now is `7.4`
- Dependency `spiral/roadrunner` (`~1.8`) changed with `spiral/roadrunner-worker` and `spiral/roadrunner-http` (`^2.0`)
- RR worker instance binding for DI from `Spiral\RoadRunner\PSR7Client` to `Spiral\RoadRunner\Http\PSR7Worker`
- `Spiral\RoadRunnerLaravel\WorkerInterface::start` accepts `Spiral\RoadRunnerLaravel\WorkerOptionsInterface` now

### Removed

- RR config file (`.rr.yaml`) publishing using `artisan vendor:publish` command
- Listener `Spiral\RoadRunnerLaravel\Listeners\ResetDbConnectionsListener`

## v3.7.0

### Added

- Support PHP `8.x`

### Changed

- Composer `2.x` is supported now
- Minimal required PHP version now is `7.3` (`7.2` security support ended January 1st, 2021)
- Dev-dependency `mockery/mockery` minimal required version changed from `^1.3.1` to `^1.3.2`
- Dev-dependency `phpstan/phpstan` minimal required version changed from `~0.12` to `~0.12.34`

### Removed

- Code-style checking and fixing for local development (packages `spiral/code-style` and `friendsofphp/php-cs-fixer` does not supports PHP `8.x`), but using GitHub this actions still running

## v3.6.0

### Changed

- Laravel `8.x` is supported now
- Minimal Laravel version now is `6.0` (Laravel `5.5` LTS got last security update August 30th, 2020)
- Minimal `spiral/roadrunner` package version now is `1.8`

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
