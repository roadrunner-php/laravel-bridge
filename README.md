<p align="center">
  <img src="https://hsto.org/webt/xl/pr/89/xlpr891cyv9ux3gm7dtzwjse_5a.png" alt="logo" width="420" />
</p>

# [RoadRunner][roadrunner] ⇆ Laravel bridge

[![Version][badge_packagist_version]][link_packagist]
[![Version][badge_php_version]][link_packagist]
[![Build Status][badge_build_status]][link_build_status]
[![Coverage][badge_coverage]][link_coverage]
[![Downloads count][badge_downloads_count]][link_packagist]
[![License][badge_license]][link_license]

Easy way for connecting [RoadRunner][roadrunner] and [Laravel][laravel] applications.

## Installation

Require this package with composer using next command:

```shell script
$ composer require spiral/roadrunner-laravel "^3.3"
```

> Installed `composer` is required ([how to install composer][getcomposer]).

> You need to fix the major version of package.

After that you can "publish" package configuration file (`./config/roadrunner.php`) using next command:

```shell script
$ php ./artisan vendor:publish --provider='Spiral\RoadRunnerLaravel\ServiceProvider' --tag=config
```

And basic RoadRunner configuration file (`./.rr.yaml.dist`):

```shell script
$ php ./artisan vendor:publish --provider='Spiral\RoadRunnerLaravel\ServiceProvider' --tag=rr-config
```

After that you can modify configuration files as you wish.

**Important**: despite the fact that worker allows you to refresh application instance on each HTTP request _(if environment variable `APP_REFRESH` set to `true`)_, we strongly recommend to avoid this for performance reasons. Large applications can be hard to integrate with RoadRunner _(you must decide which of service providers must be reloaded on each request, avoid "static optimization" in some cases)_, but it's worth it.

## Usage

After package installation you can use provided "binary" file as RoadRunner worker: `./vendor/bin/rr-worker`. This worker allows you to interact with incoming requests and outcoming responses using [laravel events system][laravel_events]. Also events contains:

Event classname              | Application object | HTTP server request | HTTP request | HTTP response | Exception
---------------------------- | :----------------: | :-----------------: | :----------: | :-----------: | :-------:
`BeforeLoopStartedEvent`     |          ✔         |                     |              |               |
`BeforeLoopIterationEvent`   |          ✔         |          ✔          |              |               |
`BeforeRequestHandlingEvent` |          ✔         |                     |       ✔      |               |
`AfterRequestHandlingEvent`  |          ✔         |                     |       ✔      |       ✔       |
`AfterLoopIterationEvent`    |          ✔         |                     |       ✔      |       ✔       |
`AfterLoopStoppedEvent`      |          ✔         |                     |              |               |
`LoopErrorOccurredEvent`     |          ✔         |          ✔          |              |               |     ✔

Simple `.rr.yaml` config example:

```yaml
env:
  #APP_REFRESH: true

http:
  address: 0.0.0.0:8080
  workers:
    command: 'php ./vendor/bin/rr-worker'

static:
  dir: 'public'
```

Roadrunner server starting:

```shell script
$ rr -c ./.rr.yaml serve -d
```

### Listeners

This package provides event listeners for resetings application state without full application reload _(like cookies, HTTP request, application instance, service-providers and other)_. Some of them already declared in configuration file, but you can declare own without any limitations.

### Environment variables

You can use the following environment variables:

Variable name     | Description
----------------- | -----------
`APP_FORCE_HTTPS` | _(declared in configuration file)_ Forces application HTTPS schema usage
`APP_REFRESH`     | Refresh application instance on every request

### Known issues

#### Controller constructors

You should avoid to use HTTP controller constructors _(created or resolved instances in constructor can be shared between different requests)_. Use dependencies resolving in controller **methods** instead.

Bad:

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $users;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param UserRepository $users
     * @param Request        $request
     */
    public function __construct(UserRepository $users, Request $request)
    {
        $this->users   = $users;
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function store(): Response
    {
        $user = $this->users->getById($this->request->id);

        // ...
    }
}
```

Good:

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * @param  Request        $request
     * @param  UserRepository $users
     *
     * @return Response
     */
    public function store(Request $request, UserRepository $users): Response
    {
        $user = $users->getById($request->id);

        // ...
    }
}
```

#### Middleware constructors

You should never to use middleware constructor for `session`, `session.store`, `auth` or auth `Guard` instances resolving and **storing** in properties _(for example)_. Use method-injection or access them through `Request` instance.

Bad:

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Session\Store;

class Middleware
{
    /**
     * @var Store
     */
    protected $session;

    /**
     * @param Store $session
     */
    public function __construct(Store $session)
    {
        $this->session = $session;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $name = $this->session->getName();

        // ...

        return $next($request);
    }
}
```

Good:

```php
<?php

use Illuminate\Http\Request;

class Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $name = $request->session()->getName();
        // $name = resolve('session')->getName();

        // ...

        return $next($request);
    }
}
```

### Testing

For package testing we use `phpunit` framework and `docker-ce` + `docker-compose` as develop environment. So, just write into your terminal after repository cloning:

```shell script
$ make build
$ make latest # or 'make lowest'
$ make test
```

## Changes log

[![Release date][badge_release_date]][link_releases]
[![Commits since latest release][badge_commits_since_release]][link_commits]

Changes log can be [found here][link_changes_log].

## Support

[![Issues][badge_issues]][link_issues]
[![Issues][badge_pulls]][link_pulls]

If you will find any package errors, please, [make an issue][link_create_issue] in current repository.

## License

MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by [tarampampam](https://github.com/tarampampam) and [Spiral Scout](https://spiralscout.com).

[badge_packagist_version]:https://img.shields.io/packagist/v/spiral/roadrunner-laravel.svg?maxAge=180
[badge_php_version]:https://img.shields.io/packagist/php-v/spiral/roadrunner-laravel.svg?longCache=true
[badge_build_status]:https://img.shields.io/github/workflow/status/spiral/roadrunner-laravel/build?maxAge=60
[badge_coverage]:https://img.shields.io/codecov/c/github/spiral/roadrunner-laravel/master.svg?maxAge=180
[badge_downloads_count]:https://img.shields.io/packagist/dt/spiral/roadrunner-laravel.svg?maxAge=180
[badge_license]:https://img.shields.io/packagist/l/spiral/roadrunner-laravel.svg?longCache=true
[badge_release_date]:https://img.shields.io/github/release-date/spiral/roadrunner-laravel.svg?style=flat-square&maxAge=180
[badge_commits_since_release]:https://img.shields.io/github/commits-since/spiral/roadrunner-laravel/latest.svg?style=flat-square&maxAge=180
[badge_issues]:https://img.shields.io/github/issues/spiral/roadrunner-laravel.svg?style=flat-square&maxAge=180
[badge_pulls]:https://img.shields.io/github/issues-pr/spiral/roadrunner-laravel.svg?style=flat-square&maxAge=180
[link_releases]:https://github.com/spiral/roadrunner-laravel/releases
[link_packagist]:https://packagist.org/packages/spiral/roadrunner-laravel
[link_build_status]:https://github.com/spiral/roadrunner-laravel/actions
[link_coverage]:https://codecov.io/gh/spiral/roadrunner-laravel/
[link_changes_log]:https://github.com/spiral/roadrunner-laravel/blob/master/CHANGELOG.md
[link_issues]:https://github.com/spiral/roadrunner-laravel/issues
[link_create_issue]:https://github.com/spiral/roadrunner-laravel/issues/new/choose
[link_commits]:https://github.com/spiral/roadrunner-laravel/commits
[link_pulls]:https://github.com/spiral/roadrunner-laravel/pulls
[link_license]:https://github.com/spiral/roadrunner-laravel/blob/master/LICENSE
[getcomposer]:https://getcomposer.org/download/
[roadrunner]:https://github.com/spiral/roadrunner
[laravel]:https://laravel.com
[laravel_events]:https://laravel.com/docs/events
[#10]:https://github.com/spiral/roadrunner-laravel/issues/10
