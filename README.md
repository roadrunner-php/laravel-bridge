<p align="center">
  <img src="https://hsto.org/webt/xl/pr/89/xlpr891cyv9ux3gm7dtzwjse_5a.png" alt="logo" width="420" />
</p>

# [RoadRunner][roadrunner] ⇆ [Laravel][laravel] bridge

[![Version][badge_packagist_version]][link_packagist]
[![Version][badge_php_version]][link_packagist]
[![License][badge_license]][link_license]

Easy way for connecting [RoadRunner][roadrunner] and [Laravel][laravel] applications (community integration).

## Why Use This Package?

This package provides complete Laravel integration with RoadRunner, offering:

- Support for HTTP and other RoadRunner plugins like gRPC, Queue, KeyValue, and more.
- [Temporal](https://temporal.io/) integration
- Full RoadRunner configuration control

![RoadRunner](https://github.com/user-attachments/assets/609d2e29-b6af-478b-b350-1d27b77ed6fb)

> [!TIP]
> [There is an article][rr-plugins-article] that explains all the RoadRunner plugins.

## Table of Contents

- [Get Started](#get-started)
  - [Installation](#installation)
  - [Configuration](#configuration)
  - [Starting the Server](#starting-the-server)
- [How It Works](#how-it-works)
- [Supported Plugins](#supported-plugins)
  - [HTTP Plugin](#http-plugin)
  - [Jobs (Queue) Plugin](#jobs-queue-plugin)
  - [gRPC Plugin](#grpc-plugin)
  - [gRPC Client](#grpc-client)
  - [Temporal](#temporal)
- [Custom Workers](#custom-workers)
- [Support](#support)
- [License](#license)

## Get Started

### Installation

First, install the Laravel Bridge package via Composer:

```shell
composer require roadrunner-php/laravel-bridge
```

Publish the configuration file:

```shell
php artisan vendor:publish --provider='Spiral\RoadRunnerLaravel\ServiceProvider' --tag=config
```

Download and install RoadRunner binary using DLoad:

```shell
./vendor/bin/dload get rr
```

### Configuration

Create a `.rr.yaml` configuration file in your project root:

```yaml
version: '3'
rpc:
  listen: 'tcp://127.0.0.1:6001'

server:
  command: 'php vendor/bin/rr-worker start'

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

### Starting the Server

Start the RoadRunner server with:

```shell
./rr serve
```

## How It Works

RoadRunner creates a worker pool by executing the command specified in the server configuration:

```yaml
server:
  command: 'php vendor/bin/rr-worker start'
```

When RoadRunner creates a worker pool for a specific plugin,
it sets the `RR_MODE` environment variable to indicate which plugin is being used.
The Laravel Bridge checks this variable to determine
which Worker class should handle the request based on your configuration.

The selected worker then listens for requests from the RoadRunner server
and handles them using the [Octane][octane] worker,
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

> [!TIP]
> Read more about the HTTP plugin in the [RoadRunner documentation][roadrunner-docs-http].

### Jobs (Queue) Plugin

The Queue plugin allows you to use RoadRunner as a queue driver for Laravel
without additional services like Redis or a database.

#### Configuration

First, add the Queue Service Provider in `config/app.php`:

```php
'providers' => [
    // ... other providers
    Spiral\RoadRunnerLaravel\Queue\QueueServiceProvider::class,
],
```

Then, configure a new connection in `config/queue.php`:

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

Set the `QUEUE_CONNECTION` environment variable in your `.env` file:

```dotenv
QUEUE_CONNECTION=roadrunner
```

That's it! You can now dispatch jobs to the RoadRunner queue without any additional services like Redis or Database.

> [!TIP]
> Read more about the Jobs plugin in the [RoadRunner documentation][roadrunner-docs-jobs].

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
        ]
    ],
];
```

#### gRPC Server Interceptors

Create your interceptor by implementing `Spiral\RoadRunnerLaravel\Grpc\GrpcServerInterceptorInterface`:

```php
<?php

namespace App\GRPC\Interceptors;

use Spiral\RoadRunnerLaravel\Grpc\GrpcServerInterceptorInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;

class LoggingInterceptor implements GrpcServerInterceptorInterface
{
    public function intercept(string $method, ContextInterface $context, string $body, callable $next)
    {
        \Log::info("gRPC call: {$method}");
        
        $response = $next($method, $context, $body);
        
        \Log::info("gRPC response: {$method}");
        
        return $response;
    }
}
```

##### Interceptors Configuration

Configure interceptors in `config/roadrunner.php`. You can use global interceptors that apply to all services, service-specific interceptors, or both:

```php
return [
    // ... other configuration
    'grpc' => [
        'services' => [
            // Simple service configuration
            \App\GRPC\EchoServiceInterface::class => \App\GRPC\EchoService::class,

            // Service with specific interceptors
            \App\GRPC\UserServiceInterface::class => [
                \App\GRPC\UserService::class,
                'interceptors' => [
                    \App\GRPC\Interceptors\ValidationInterceptor::class,
                    \App\GRPC\Interceptors\CacheInterceptor::class,
                ],
            ],
        ],
        // Global interceptors - applied to all services
        'interceptors' => [
            \App\GRPC\Interceptors\LoggingInterceptor::class,
            \App\GRPC\Interceptors\AuthenticationInterceptor::class,
        ],
    ],
];
```

#### gRPC Client Usage

The package also allows your Laravel application to act as a gRPC client, making requests to external gRPC services.

##### Client Configuration

Add your gRPC client configuration to `config/roadrunner.php`:

```php
return [
    // ... other configuration
    'grpc' => [
        // ... server config
        'clients' => [
            'services' => [
                [
                    'connection' => '127.0.0.1:9001', // gRPC server address
                    'interfaces' => [
                        \App\Grpc\EchoServiceInterface::class,
                    ],
                    // 'tls' => [ ... ] // Optional TLS configuration
                ],
            ],
            // 'interceptors' => [ ... ] // Optional interceptors
        ],
    ],
];
```

##### Using the gRPC Client in Laravel

You can inject `Spiral\Grpc\Client\ServiceClientProvider` into your services or controllers to obtain a gRPC client instance:

```php
use Spiral\Grpc\Client\ServiceClientProvider;
use App\Grpc\EchoServiceInterface;
use App\Grpc\EchoRequest;

class GrpcController extends Controller
{
    public function callService(ServiceClientProvider $provider)
    {
        /** @var EchoServiceInterface $client */
        $client = $provider->get(EchoServiceInterface::class);

        $request = new EchoRequest();
        $request->setMessage('Hello from client!');

        $response = $client->Echo($request);

        return $response->getMessage();
    }
}
```

> **Note:**
> - Make sure you have generated the PHP classes from your `.proto` files (using `protoc`).
> - The `connection` and `interfaces` must match the service you want to call.
> - You can configure multiple gRPC client services as needed.

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

Download Temporal binary for development:

```bash
./vendor/bin/dload get temporal
```

Start the Temporal dev server:

```bash
./temporal server start-dev --log-level error --color always
```

#### Useful Links

- [PHP SDK on GitHub](https://github.com/temporalio/sdk-php)
- [PHP SDK docs](https://docs.temporal.io/develop/php/)
- [Code samples](https://github.com/temporalio/samples-php)
- [Taxi service sample](https://github.com/butschster/podlodka-taxi-service)

## Custom Workers

The RoadRunner Laravel Bridge comes with several predefined workers for common plugins,
but you can easily create your own custom workers for any RoadRunner plugin.
This section explains how to create and register custom workers in your application.

### Understanding Workers

Workers are responsible for handling requests from the RoadRunner server
and processing them in your Laravel application.
The predefined workers are configured in the `config/roadrunner.php` file:

```php
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

### Creating Custom Workers

To create a custom worker, you need to implement the `Spiral\RoadRunnerLaravel\WorkerInterface`.
This interface has a single method, `start()`, which is called when the worker is started by the RoadRunner server:

```php
namespace App\Workers;

use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;

class CustomWorker implements WorkerInterface
{
    public function start(WorkerOptionsInterface $options): void
    {
        // Your worker implementation goes here
        // This method should handle requests from the RoadRunner server
    }
}
```

### Registering Custom Workers

After creating your custom worker, you need to register it in the `config/roadrunner.php` file:

```php
return [
    // ... other configuration options ...

    'workers' => [
        // Existing workers
        Mode::MODE_HTTP => HttpWorker::class,
        Mode::MODE_JOBS => QueueWorker::class,

        // Your custom worker for a custom or built-in plugin
        'custom_plugin' => \App\Workers\CustomWorker::class,
    ],
];
```

The key in the `workers` array should match the value of the `RR_MODE` environment variable
set by the RoadRunner server for your plugin.

## Support

If you find this package helpful, please consider giving it a star on GitHub.
Your support helps make the project more visible to other developers who might benefit from it!

[![Issues][badge_issues]][link_issues]
[![Issues][badge_pulls]][link_pulls]

If you find any package errors, please, [make an issue][link_create_issue] in a current repository.

You can also [sponsor this project][link_sponsor] to help ensure its continued development and maintenance.

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

[link_sponsor]:https://github.com/sponsors/roadrunner-server

[link_license]:https://github.com/roadrunner-php/laravel-bridge/blob/master/LICENSE

[getcomposer]:https://getcomposer.org/download/

[dload]:https://github.com/php-internal/dload

[roadrunner]:https://github.com/roadrunner-server/roadrunner

[roadrunner_config]:https://github.com/roadrunner-server/roadrunner/blob/master/.rr.yaml

[laravel]:https://laravel.com

[laravel_events]:https://laravel.com/docs/events

[roadrunner-binary-releases]:https://github.com/roadrunner-server/roadrunner/releases

[roadrunner-docs-jobs]:https://docs.roadrunner.dev/docs/queues-and-jobs/overview-queues

[roadrunner-docs-http]:https://docs.roadrunner.dev/docs/http/http

[octane]:https://laravel.com/docs/12.x/octane

[rr-plugins-article]:https://butschster.medium.com/roadrunner-an-underrated-powerhouse-for-php-applications-46410b0abc
