# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][keepachangelog] and this project adheres to [Semantic Versioning][semver].

## v5.10.0

### Added

- Laravel 10 support [#111]

### Changed

- Minimal required PHP version now is `8.0` [#111]
- Minimal required Laravel version now is `9.33` [#111]

[#111]:https://github.com/spiral/roadrunner-laravel/pull/111

## v5.9.0

### Added

- Integration with [spatie/laravel-ignition](https://github.com/spatie/laravel-ignition) is supported now [#88]
- Defining of environment variable `APP_RUNNING_IN_CONSOLE` in the worker "binary" file (it is required for the correct [`Application::runningInConsole`](https://bit.ly/3GPJTNL) method working) [#88]

### Fixed

- CLI running mode detection [#88]

[#88]:https://github.com/spiral/roadrunner-laravel/pull/88

## v5.8.0

### Added

- Listener `CleanupUploadedFilesListener` for removing temporary files which were created during uploading _(must be enabled manually for the `AfterLoopIterationEvent` event)_ [#84]

[#84]:https://github.com/spiral/roadrunner-laravel/issues/84

## v5.7.0

### Added

- Laravel 9 support [#78]
- Listener `FlushStrCacheListener` for flushing `Str` cache between requests [#86]

### Removed

- Laravel 6 and 7 is no longer supported [#78]

[#78]:https://github.com/spiral/roadrunner-laravel/pull/78
[#86]:https://github.com/spiral/roadrunner-laravel/pull/86

## v5.6.0

### Added

- Give the current App instance to `FilesystemManager` (listener `RebindFilesystemManagerListener`) [#77]
- Monolog state resetting between requests (listener `FlushMonologStateListener`) [#77]

[#77]:https://github.com/spiral/roadrunner-laravel/pull/77

## v5.5.0

### Added

- Listener `FlushTranslatorCacheListener` for memory leak fixing on `Translator` implementation [#70]
- Integration with [Livewire](https://github.com/livewire/livewire) is supported now [#71]

[#70]:https://github.com/spiral/roadrunner-laravel/pull/70
[#71]:https://github.com/spiral/roadrunner-laravel/pull/71

## v5.4.0

### Added

- Listener `FlushDatabaseQueryLogListener` for cleaning up database query log [#67]

[#67]:https://github.com/spiral/roadrunner-laravel/pull/67

## v5.3.0

### Added

- Possibility to use different classes of workers for different worker modes [#65]
- Integration with [Ziggy](https://github.com/tighten/ziggy) is supported now [#64]

### Changed

- Listeners (resetters) for the 3rd party packages are enabled by default

[#64]:https://github.com/spiral/roadrunner-laravel/issues/64
[#65]:https://github.com/spiral/roadrunner-laravel/issues/65

## v5.2.2

### Changed

- Resolve listener components when needed [#58]

[#58]:https://github.com/spiral/roadrunner-laravel/issues/58

## v5.2.1

### Fixed

- Dumper middleware could not dump a large set of responses (such as `Illuminate\Http\JsonResponse`)

## v5.2.0

### Added

- Integration with [Laravel Telescope](https://github.com/laravel/telescope/) is supported now (just enable `SetupTelescopeListener` for `BeforeLoopStartedEvent`) [#53]

[#53]:https://github.com/spiral/roadrunner-laravel/issues/53

## v5.1.0

### Added

- Listener `FlushLogContextListener` for the logger context flushing [#51]

[#51]:https://github.com/spiral/roadrunner-laravel/pull/51

## v5.0.2

### Fixed

- Dumper CLI mode detection [#47]

[#47]:https://github.com/spiral/roadrunner-laravel/pull/47

## v5.0.1

### Fixed

- Symfony uploaded file moving (`FixSymfonyFileMovingListener` was added for this) [#43]

[#43]:https://github.com/spiral/roadrunner-laravel/issues/43

## v5.0.0

### Added

- Listener `RebindDatabaseSessionHandlerListener` for the database session driver container rebinding [[octane#300](https://github.com/laravel/octane/issues/300)]
- Listener `WarmInstancesListener` for instances pre-resolving

### Changed

- Most important configuration values (such as event listeners) now defined in `Spiral\RoadRunnerLaravel\Defaults` class and used by the package configuration file (in the future, you will not need to update your config file manually when new "core" listeners will be added)
- Dependency `laminas/laminas-diactoros` replaced with [`nyholm/psr7`](https://github.com/Nyholm/psr7) (lightweight PSR-7 implementation, strict and fast)
- Config option `pre_resolving` replaced with `warm`
- Config option `clear_instances` replaced with `clear`
- Worker code refactored

## v4.1.0

### Added

- Possibility to "dump" (using [Symfony VarDumper](https://github.com/symfony/var-dumper)) any variables in HTTP context (just call `\rr\dump(...)` or `\rr\dd(...)` instead `dump(...)` or `dd(...)` in your code)
- Function `\rr\worker()` for easy access to the RoadRunner PSR worker instance (available only in HTTP context, of course)
- Listener `FlushArrayCacheListener` for flushing `array`-based cache storages
- Listener `FlushAuthenticationStateListener` for authentication state flushing
- Listener `RebindAuthorizationGateListener` for the authorization gate container rebinding
- Listener `RebindBroadcastManagerListener` for the broadcast manager container rebinding
- Listener `RebindDatabaseManagerListener` for the database manager container rebinding
- Listener `RebindMailManagerListener` for the mail manager container rebinding and resolved mailer instances clearing
- Listener `RebindNotificationChannelManagerListener` for the notification channel manager container rebinding and resolved driver instances clearing
- Listener `RebindPipelineHubListener` for the pipeline hub container rebinding
- Listener `RebindQueueManagerListener` for the queue manager container rebinding
- Listener `RebindValidationFactoryListener` for the validator container rebinding
- Listener `ResetDatabaseRecordModificationStateListener` for resetting the database record modification state
- Listener `ResetLocaleStateListener` for the translator locale resetting
- Integration with [inertiajs](https://inertiajs.com/) (package [inertiajs/inertia-laravel](https://github.com/inertiajs/inertia-laravel)) is supported now (just enable `ResetInertiaListener` for `BeforeLoopIterationEvent`)
- Integration with [Laravel Scout](https://laravel.com/docs/master/scout/) is supported now (just enable `ResetLaravelScoutListener` for `BeforeLoopIterationEvent`)
- Integration with [Laravel Socialite](https://github.com/laravel/socialite/) is supported now (just enable `ResetLaravelSocialiteListener` for `BeforeLoopIterationEvent`)

### Changed

- Listeners `RebindHttpKernelListener`, `RebindRouterListener`, `RebindViewListener` and `UnqueueCookiesListener` improved

## v4.0.1

### Fixed

- Termination request handling (safe loop breaking)

## v4.0.0

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
