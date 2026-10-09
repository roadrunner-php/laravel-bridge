<p align="center">
    <a href="https://roadrunner.dev"><picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
        <img alt="RoadRunner" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8" style="width: 6in; display: block">
    </picture></a>
</p>

<p align="center">RoadRunner ⇆ Laravel bridge</p>

<div align="center">

[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/roadrunner-server)

[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Froadrunner-php%2Flaravel-bridge%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/roadrunner-php/laravel-bridge/master)

</div>

<br />

Run [Laravel][laravel] applications on [RoadRunner][roadrunner]: serve HTTP and gRPC, process queues, and run Temporal workflows and activities in long-living workers, with the application state reset between tasks by [Octane][octane].

The package provides complete Laravel integration with RoadRunner:

- Support for HTTP and other RoadRunner plugins like gRPC, Queue, KeyValue, and more.
- [Temporal](https://temporal.io/) integration
- Full RoadRunner configuration control
- PSR-3 compatible logging with RoadRunner integration

![RoadRunner](https://github.com/user-attachments/assets/609d2e29-b6af-478b-b350-1d27b77ed6fb)

> [!TIP]
> [There is an article][rr-plugins-article] that explains all the RoadRunner plugins.

## Get Started

### Installation

```bash
composer require roadrunner-php/laravel-bridge
```

[![PHP](https://img.shields.io/packagist/php-v/roadrunner-php/laravel-bridge.svg?style=flat-square&logo=php)](https://packagist.org/packages/roadrunner-php/laravel-bridge)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/roadrunner-php/laravel-bridge.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/roadrunner-php/laravel-bridge)
[![License](https://img.shields.io/packagist/l/roadrunner-php/laravel-bridge.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/roadrunner-php/laravel-bridge.svg?style=flat-square)](https://packagist.org/packages/roadrunner-php/laravel-bridge/stats)

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

The `roadrunner` queue driver is registered by `Spiral\RoadRunnerLaravel\Queue\QueueServiceProvider`,
which Laravel loads automatically via package discovery.

Configure a new connection in `config/queue.php`:

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

            // Service with specific interceptors
            \App\GRPC\UserServiceInterface::class => [
                'service' => \App\GRPC\UserService::class,
                'interceptors' => [
                    \App\GRPC\Interceptors\ValidationInterceptor::class,
                    \App\GRPC\Interceptors\CacheInterceptor::class,
                ],
            ],
        ]
    ],
];
```

#### gRPC Server Interceptors

Create your interceptor by implementing `Spiral\Interceptors\InterceptorInterface`:

```php
<?php

namespace App\GRPC\Interceptors;

use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

class LoggingInterceptor implements InterceptorInterface
{
    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $method = implode('.', $context->getTarget()->getPath());
        \Log::info("gRPC call: {$method}");

        $response = $handler->handle($context);

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
                'service' => \App\GRPC\UserService::class,
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

##### Using Attribute-Based Interceptors

For additional flexibility and convenience, you can use the `AttributesInterceptor` to apply interceptors via PHP attributes directly on your service classes and methods. This allows you to define which interceptors should be applied at a more granular level.

To enable attribute-based interceptors, add the `AttributesInterceptor` to your global interceptors list:

```php
'interceptors' => [
    // ... other global interceptors before
    \Spiral\RoadRunnerLaravel\Common\Interceptor\AttributesInterceptor::class,
    // ... other global interceptors after
],
```

Then, create interceptors that can be used as attributes:

```php
<?php

namespace App\GRPC\Interceptors;

use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RoleInterceptor implements InterceptorInterface
{
    public function __construct(
        private readonly string $role,
    ) {}

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        // Check user role
        if (!$this->checkRole($this->role)) {
            throw new \RuntimeException('Access denied');
        }

        return $handler->handle($context);
    }
}
```

Apply interceptors to your gRPC service classes or methods:

```php
<?php

namespace App\GRPC;

use App\GRPC\Interceptors\LoggingInterceptor;
use App\GRPC\Interceptors\AuthInterceptor;
use App\GRPC\Interceptors\RoleInterceptor;

#[LoggingInterceptor]
#[AuthInterceptor]
class UserService implements UserServiceInterface
{
    #[RoleInterceptor('admin')]
    public function DeleteUser(GRPC\ContextInterface $ctx, DeleteUserRequest $in): DeleteUserResponse
    {
        // Implementation - will use class-level + method-level interceptors
    }

    public function GetUser(GRPC\ContextInterface $ctx, GetUserRequest $in): GetUserResponse
    {
        // Implementation - will use only class-level interceptors
    }
}
```

Interceptors are applied in order: first class-level attributes, then method-level attributes.

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
        'temporalNamespace' => 'default',
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

## Logging

The RoadRunner PSR Logger provides PSR-3 compatible logging with RoadRunner integration. The logger uses RPC calls to send logs to RoadRunner's centralized logging system, providing proper log level control and structured context support.

### Usage

The RoadRunner PSR Logger is available throughout your application via dependency injection:

#### Dependency Injection (Recommended)

```php
use Psr\Log\LoggerInterface;

class YourService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function doSomething()
    {
        $this->logger->info('Operation started');

        try {
            // Your logic here
            $this->logger->info('Operation completed');
        } catch (\Exception $e) {
            $this->logger->error('Operation failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
```

#### Service Container Resolution

```php
// Get logger from container
$logger = app(LoggerInterface::class);
// Or using the alias
$logger = app('roadrunner.logger');

$logger->info('Message', ['context' => 'data']);
```

#### Useful Links
- [RoadRunner PSR Logger](https://github.com/roadrunner-php/psr-logger)

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

### Example: Centrifugo Worker

Here's an example of a custom worker for the [Centrifuge](https://docs.roadrunner.dev/docs/plugins/centrifuge) plugin,
built on the [roadrunner-php/centrifugo](https://github.com/roadrunner-php/centrifugo) package:

```php
namespace App\Workers;

use Laravel\Octane\ApplicationFactory;
use RoadRunner\Centrifugo\CentrifugoWorker as RRCentrifugoWorker;
use RoadRunner\Centrifugo\Payload;
use RoadRunner\Centrifugo\Request;
use RoadRunner\Centrifugo\Request\RequestFactory;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;

class CentrifugoWorker implements WorkerInterface
{
    public function start(WorkerOptionsInterface $options): void
    {
        $app = (new ApplicationFactory($options->getAppBasePath()))->createApplication();
        // Resolve your handlers from the booted Laravel application, e.g. $app->make(...)

        $worker = Worker::create();
        $centrifugo = new RRCentrifugoWorker($worker, new RequestFactory($worker));

        while ($request = $centrifugo->waitRequest()) {
            try {
                match (true) {
                    // Handle client connection
                    $request instanceof Request\Connect => $request->respond(new Payload\ConnectResponse(
                        user: 'user-id',
                    )),
                    // Handle client subscription
                    $request instanceof Request\Subscribe => $request->respond(new Payload\SubscribeResponse()),
                    // Handle client publish
                    $request instanceof Request\Publish => $request->respond(new Payload\PublishResponse()),
                    default => null,
                };
            } catch (\Throwable $e) {
                $request->error((int) $e->getCode(), $e->getMessage());
            }
        }
    }
}
```

Then register it in your configuration:

```php
use Spiral\RoadRunner\Environment\Mode;

return [
    'workers' => [
        // ... other workers
        Mode::MODE_CENTRIFUGE => \App\Workers\CentrifugoWorker::class,
    ],
];
```

And update your `.rr.yaml` with the Centrifuge plugin configuration:

```yaml
centrifuge:
  proxy_address: "tcp://0.0.0.0:10001"
  grpc_api_address: "localhost:10000"
```

## Support

If you find this package helpful, please consider giving it a star on GitHub.
Your support helps make the project more visible to other developers who might benefit from it!

[![Issues][badge_issues]][link_issues]
[![Issues][badge_pulls]][link_pulls]

If you find any package errors, please, [make an issue][link_create_issue] in a current repository.

You can also [sponsor this project][link_sponsor] to help ensure its continued development and maintenance.

[badge_issues]:https://img.shields.io/github/issues/roadrunner-php/laravel-bridge.svg?style=flat-square&maxAge=180

[badge_pulls]:https://img.shields.io/github/issues-pr/roadrunner-php/laravel-bridge.svg?style=flat-square&maxAge=180

[link_issues]:https://github.com/roadrunner-php/laravel-bridge/issues

[link_create_issue]:https://github.com/roadrunner-php/laravel-bridge/issues/new/choose

[link_pulls]:https://github.com/roadrunner-php/laravel-bridge/pulls

[link_sponsor]:https://github.com/sponsors/roadrunner-server

[roadrunner]:https://github.com/roadrunner-server/roadrunner

[laravel]:https://laravel.com

[roadrunner-docs-jobs]:https://docs.roadrunner.dev/docs/queues-and-jobs/overview-queues

[roadrunner-docs-http]:https://docs.roadrunner.dev/docs/http/http

[octane]:https://laravel.com/docs/octane

[rr-plugins-article]:https://butschster.medium.com/roadrunner-an-underrated-powerhouse-for-php-applications-46410b0abc
