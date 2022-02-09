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
        Str::snake('Hello world');

        $reflection = new \ReflectionClass(Str::class);
        $property = $reflection->getProperty('snakeCache');
        $property->setAccessible(true);

        $this->assertNotEmpty($property->getValue());

        $this->listenerFactory()->handle(new \stdClass());

        $this->assertEmpty($property->getValue());
    }

    /**
     * @return FlushStrCacheListener
     */
    protected function listenerFactory(): FlushStrCacheListener
    {
        return new FlushStrCacheListener();
    }
}
