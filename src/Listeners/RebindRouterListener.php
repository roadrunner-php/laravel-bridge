<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/RebindRouterContainer.php
 */
class RebindRouterListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication && $event instanceof WithHttpRequest) {
            $app     = $event->application();
            $request = $event->httpRequest();

            /** @var \Illuminate\Routing\Router $router */
            $router  = $app->make('router');

            $closure = function () use ($app, $request): void {
                $this->{'container'} = $app;

                try {
                    $request->enableHttpMethodParameterOverride();
                    /** @var mixed $route */
                    $route = $this->{'getRoutes'}()->match($request);

                    // rebind resolved controller
                    if (\property_exists($route, $container_property = 'container')) {
                        $rebind_closure = function () use ($container_property, $app): void {
                            $this->{$container_property} = $app;
                        };

                        $rebind = $rebind_closure->bindTo($route, $route);
                        $rebind();
                    }

                    // rebind matched route's container
                    $route->setContainer($app);
                } catch (HttpException $e) {
                    // do nothing
                }
            };

            // Black magic in action
            $reset_router = $closure->bindTo($router, $router);
            $reset_router();
        }
    }
}
