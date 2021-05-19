<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Dumper;

use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Spiral\RoadRunnerLaravel\Dumper\Middleware;

/**
 * @covers \Spiral\RoadRunnerLaravel\Dumper\Middleware
 */
class MiddlewareTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    protected Router $router;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        \putenv('RR_MODE=http');
        \putenv('RR_RPC=tcp://127.0.0.1:9001');
        \putenv('RR_RELAY=pipes');

        parent::setUp();

        $this->router = $this->app->make(Router::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        \putenv('RR_MODE');
        \putenv('RR_RPC');
        \putenv('RR_RELAY');
    }

    public function testDumpWithMiddlewareWorking(): void
    {
        $to_dump = Str::random();

        $this->router->get($path = '/' . Str::random(), function () use (&$to_dump) {
            \rr\dump($to_dump);

            return new Response('foo bar');
        })->middleware(Middleware::class);

        $response = $this->get($path);

        foreach ([$to_dump, 'foo bar', 'window.Sfdump', '<script'] as $want) {
            $this->assertStringContainsString($want, $response->getContent());
        }
    }

    public function testDdWithMiddlewareWorking(): void
    {
        $to_dump = Str::random();

        $this->router->get($path = '/' . Str::random(), function () use (&$to_dump) {
            \rr\dd($to_dump);

            return new Response('foo bar');
        })->middleware(Middleware::class);

        $response = $this->get($path);

        foreach (['<html', $to_dump, 'window.Sfdump', '<script'] as $want) {
            $this->assertStringContainsString($want, $response->getContent());
        }

        $this->assertStringNotContainsString('foo bar', $response->getContent());
    }
}
