<p align="center">
  <img src="https://hsto.org/webt/xl/pr/89/xlpr891cyv9ux3gm7dtzwjse_5a.png" alt="logo" width="420" />
</p>

# [RoadRunner][roadrunner] ⇆ [Laravel][laravel] bridge

[![Version][badge_packagist_version]][link_packagist]
[![Version][badge_php_version]][link_packagist]
[![License][badge_license]][link_license]

Easy way for connecting [RoadRunner][roadrunner] and [Laravel][laravel] applications (community integration).

## Why Use This Package?

Laravel provides the [Octane](https://laravel.com/docs/12.x/octane) package which partially supports RoadRunner as an
application server, but RoadRunner offers much more than just HTTP capabilities. It also includes Jobs, Temporal, gRPC,
and other plugins.

![RoadRunner](https://github.com/user-attachments/assets/609d2e29-b6af-478b-b350-1d27b77ed6fb)

> **Note:** There is an article that explains all the RoadRunner
> plugins: https://butschster.medium.com/roadrunner-an-underrated-powerhouse-for-php-applications-46410b0abc

The main limitation of Octane is that it has a built-in worker only for the HTTP plugin and doesn't provide the ability
to create additional workers for other RoadRunner plugins, restricting its use to just the HTTP plugin.

Our **Laravel Bridge** solves this problem by taking a different approach:

1. We include `laravel/octane` in our package and reuse its **SDK** for clearing the state of Laravel applications
2. We add support for running and configuring multiple workers for different RoadRunner plugins
3. By reusing Octane's functionality for state clearing, we automatically support all third-party packages that are
   compatible with Octane

**This way, you get the best of both worlds:** Octane's state management and RoadRunner's full plugin ecosystem.

## Installation

```shell script
composer require roadrunner-php/laravel-bridge
```

After that you can "publish" package configuration file (`./config/roadrunner.php`) using next command:

```shell script
php artisan vendor:publish --provider='Spiral\RoadRunnerLaravel\ServiceProvider' --tag=config
```

## Usage

After package installation, you can download and install [RoadRunner][roadrunner] binary
using [roadrunner-cli][roadrunner-cli]:

```bash
./vendor/bin/rr get
```

### Basic Configuration (.rr.yaml)

Create a `.rr.yaml` configuration file in your project root:

```yaml
version: '3'
rpc:
  listen: 'tcp://127.0.0.1:6001'

server:
  command: 'php vendor/bin/rr-worker start'
  relay: pipes

http:
  address: 0.0.0.0:8080
  middleware: [ "static", "headers", "gzip" ]
  pool:
    #max_jobs: 64 # feel free to change this
    supervisor:
      exec_ttl: 60s
  headers:
    response:
      X-Powered-By: "RoadRunner"
  static:
    dir: "public"
    forbid: [ ".php" ]
```

## RoadRunner Worker Configuration

You can configure workers in `config/roadrunner.php` file in the `workers` section:

```php
use Spiral\RoadRunner\Environment\Mode;
use Spiral\RoadRunnerLaravel\Grpc\GrpcWorker;
use Spiral\RoadRunnerLaravel\Http\HttpWorker;
use Spiral\RoadRunnerLaravel\Queue\QueueWorker;
use Spiral\RoadRunnerLaravel\Temporal\TemporalWorker;

return [
    // ... other configuration options ...

    'workers' => [
        Mode::MODE_HTTP => HttpWorker::class,
        Mode::MODE_JOBS => QueueWorker::class,
        Mode::MODE_GRPC => GrpcWorker::class,
        Mode::MODE_TEMPORAL => TemporalWorker::class,
    ],
];
```

As you can see, there are several predefined workers for HTTP, Jobs, gRPC, and Temporal. Feel free to replace any of
them with your implementation if needed. Or create your own worker, for example,
for [Centrifugo](https://docs.roadrunner.dev/docs/plugins/centrifuge), [TCP](https://docs.roadrunner.dev/docs/plugins/tcp)
or any other plugin.

## How It Works

In the server section of the RoadRunner config, we specify the command to start our worker:

```yaml
server:
  command: 'php vendor/bin/rr-worker start'
  relay: pipes
```

When RoadRunner server creates a worker pool for a specific plugin, it exposes an environment variable `RR_MODE` that
indicates which plugin is being used. Our worker checks this variable to determine which Worker class should handle the
request based on the configuration in `roadrunner.php`.

The selected worker starts listening for requests from the RoadRunner server and handles them using the Octane worker,
which clears the application state after each task (request, command, etc.).

## Supported Plugins

### HTTP Plugin

The HTTP plugin enables serving HTTP requests with your Laravel application through RoadRunner.

#### Configuration

Ensure your `.rr.yaml` has the HTTP section configured:

```yaml
http:
  address: 0.0.0.0:8080
  middleware: [ "static", "headers", "gzip" ]
  pool:
    max_jobs: 64
  static:
    dir: "public"
    forbid: [ ".php" ]
```

> **Note:** Read more about the HTTP plugin in
> the [RoadRunner documentation][https://docs.roadrunner.dev/docs/http/http].

### Jobs (Queue) Plugin

The Queue plugin allows you to use RoadRunner as a queue driver for Laravel.

#### Configuration

First, add the Queue Service Provider in your `config/app.php`:

```php
'providers' => [
    // ... other providers
    Spiral\RoadRunnerLaravel\Queue\QueueServiceProvider::class,
],
```

Then, configure a new connection in your `config/queue.php`:

```php
'connections' => [
    // ... other connections
   'roadrunner' => [
      'driver' => 'roadrunner',
      'queue' => env('RR_QUEUE', 'default'),
      'retry_after' => (int) env('RR_QUEUE_RETRY_AFTER', 90),
      'after_commit' => false,
   ],
],
```

Update your `.rr.yaml` file to include the Jobs section:

```yaml
jobs:
  pool:
    num_workers: 4
  pipelines:
    default:
      driver: memory
      config: { }
```

> **Note:** Read more about the Jobs plugin in
> the [RoadRunner documentation][https://docs.roadrunner.dev/docs/queues-and-jobs/overview-queues].

Don't forget to set the `QUEUE_CONNECTION` environment variable in your `.env` file:

```dotenv
QUEUE_CONNECTION=roadrunner
```

That's it! You can now dispatch jobs to the RoadRunner queue without any additional services like Redis or Database.

### gRPC Plugin

The gRPC plugin enables serving gRPC services with your Laravel application.

#### Configuration

Configure gRPC in your `.rr.yaml`:

```yaml
grpc:
  listen: 'tcp://0.0.0.0:9001'
  proto:
    - "proto/service.proto"
```

Then, add your gRPC services to `config/roadrunner.php`:

```php
return [
    // ... other configuration
    'grpc' => [
        'services' => [
            \App\GRPC\EchoServiceInterface::class => \App\GRPC\EchoService::class,
        ],
    ],
];
```

### Temporal

Temporal is a workflow engine that enables orchestration of microservices and provides sophisticated workflow
mechanisms.

#### Configuration

First, configure Temporal in your `.rr.yaml`:

```yaml
temporal:
  address: 127.0.0.1:7233
  activities:
    num_workers: 10
```

Then, configure your workflows and activities in `config/roadrunner.php`:

```php
return [
    // ... other configuration
    'temporal' => [
        'address' => env('TEMPORAL_ADDRESS', '127.0.0.1:7233'),
        'namespace' => 'default',
        'declarations' => [
            \App\Temporal\Workflows\MyWorkflow::class,
            \App\Temporal\Activities\MyActivity::class,
        ],
    ],
];
```

#### Useful links

- [PHP SDK docs](https://docs.temporal.io/develop/php/) 
- [Code samples](https://github.com/temporalio/samples-php)
- [Taxi service sample](https://github.com/butschster/podlodka-taxi-service)

## Starting RoadRunner Server

To start the RoadRunner server:

```shell script
./rr serve
```

## Custom Workers

You can create your own custom workers by implementing the `Spiral\RoadRunnerLaravel\WorkerInterface`:

```php
namespace App\Workers;

use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;

class CustomWorker implements WorkerInterface
{
    public function start(WorkerOptionsInterface $options): void
    {
        // Your custom worker implementation
    }
}
```

Then register it in the `config/roadrunner.php`:

```php
return [
    'workers' => [
        'custom' => \App\Workers\CustomWorker::class,
    ],
];
```

## Support

If you find this package helpful, please consider giving it a star on GitHub. Your support helps make the project more visible to other developers who might benefit from it!

[![Issues][badge_issues]][link_issues]
[![Issues][badge_pulls]][link_pulls]

If you find any package errors, please, [make an issue][link_create_issue] in a current repository.

## License

MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.

[badge_packagist_version]:https://img.shields.io/packagist/v/roadrunner-php/laravel-bridge.svg?maxAge=180

[badge_php_version]:https://img.shields.io/packagist/php-v/roadrunner-php/laravel-bridge.svg?longCache=true

[badge_build_status]:https://img.shields.io/github/actions/workflow/status/roadrunner-php/laravel-bridge/tests.yml?branch=master&maxAge=30

[badge_chat]:https://img.shields.io/badge/discord-chat-magenta.svg

[badge_coverage]:https://img.shields.io/codecov/c/github/roadrunner-php/laravel-bridge/master.svg?maxAge=180

[badge_downloads_count]:https://img.shields.io/packagist/dt/roadrunner-php/laravel-bridge.svg?maxAge=180

[badge_license]:https://img.shields.io/packagist/l/roadrunner-php/laravel-bridge.svg?maxAge=256

[badge_release_date]:https://img.shields.io/github/release-date/roadrunner-php/laravel-bridge.svg?style=flat-square&maxAge=180

[badge_commits_since_release]:https://img.shields.io/github/commits-since/roadrunner-php/laravel-bridge/latest.svg?style=flat-square&maxAge=180

[badge_issues]:https://img.shields.io/github/issues/roadrunner-php/laravel-bridge.svg?style=flat-square&maxAge=180

[badge_pulls]:https://img.shields.io/github/issues-pr/roadrunner-php/laravel-bridge.svg?style=flat-square&maxAge=180

[link_releases]:https://github.com/roadrunner-php/laravel-bridge/releases

[link_packagist]:https://packagist.org/packages/roadrunner-php/laravel-bridge

[link_build_status]:https://github.com/roadrunner-php/laravel-bridge/actions

[link_chat]:https://discord.gg/Y3df23vJDw

[link_coverage]:https://codecov.io/gh/roadrunner-php/laravel-bridge/

[link_changes_log]:https://github.com/roadrunner-php/laravel-bridge/blob/master/CHANGELOG.md

[link_issues]:https://github.com/roadrunner-php/laravel-bridge/issues

[link_create_issue]:https://github.com/roadrunner-php/laravel-bridge/issues/new/choose

[link_commits]:https://github.com/roadrunner-php/laravel-bridge/commits

[link_pulls]:https://github.com/roadrunner-php/laravel-bridge/pulls

[link_license]:https://github.com/roadrunner-php/laravel-bridge/blob/master/LICENSE

[getcomposer]:https://getcomposer.org/download/

[roadrunner]:https://github.com/roadrunner-server/roadrunner

[roadrunner_config]:https://github.com/roadrunner-server/roadrunner/blob/master/.rr.yaml

[laravel]:https://laravel.com

[laravel_events]:https://laravel.com/docs/events

[roadrunner-cli]:https://github.com/spiral/roadrunner-cli

[roadrunner-binary-releases]:https://github.com/roadrunner-server/roadrunner/releases
