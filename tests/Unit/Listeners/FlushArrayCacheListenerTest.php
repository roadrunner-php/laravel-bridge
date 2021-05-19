<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Support\Str;
use Illuminate\Cache\CacheManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spiral\RoadRunnerLaravel\Listeners\FlushArrayCacheListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushArrayCacheListener
 */
class FlushArrayCacheListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);

        // declare custom cache storage with array driver
        $config->set('cache.stores.' . ($storage_name = 'test_' . Str::random()), [
            'driver'    => 'array',
            'serialize' => false,
        ]);

        /** @var CacheManager $cache_manager */
        $cache_manager = $this->app->make('cache');

        // put something into own storage
        $cache_manager->store($storage_name)->set($key_name = Str::random(), 'bar');

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        // must be exists before the listener handling
        $this->assertSame('bar', $cache_manager->store($storage_name)->get($key_name));

        $this->listenerFactory()->handle($event_mock);

        // and must be removed AFTER the listener handling
        $this->assertSame(null, $cache_manager->store($storage_name)->get($key_name));
    }

    /**
     * @return FlushArrayCacheListener
     */
    protected function listenerFactory(): FlushArrayCacheListener
    {
        return new FlushArrayCacheListener();
    }
}
