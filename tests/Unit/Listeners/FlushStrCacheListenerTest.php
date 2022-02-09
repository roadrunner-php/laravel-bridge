<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Illuminate\Support\Str;
use Spiral\RoadRunnerLaravel\Listeners\FlushStrCacheListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushStrCacheListenerTest
 */
class FlushStrCacheListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        if (! \method_exists(Str::class, $method_name = 'flushCache')) {
            $this->markTestSkipped("Method $method_name does not exist");
        }

        Str::snake('Hello world');
        Str::camel('Hello world');
        Str::studly('Hello world');

        $reflection = new \ReflectionClass(Str::class);

        /** @var array<\ReflectionProperty> $props */
        $props = [
            $reflection->getProperty('snakeCache'),
            $reflection->getProperty('camelCache'),
            $reflection->getProperty('studlyCache'),
        ];

        foreach ($props as $prop) {
            $prop->setAccessible(true);

            $this->assertNotEmpty($prop->getValue());
        }

        $this->listenerFactory()->handle(new \stdClass());

        foreach ($props as $prop) {
            $this->assertEmpty($prop->getValue());
        }
    }

    /**
     * @return FlushStrCacheListener
     */
    protected function listenerFactory(): FlushStrCacheListener
    {
        return new FlushStrCacheListener();
    }
}
