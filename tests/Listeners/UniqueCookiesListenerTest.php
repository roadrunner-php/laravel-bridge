<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\UnqueueCookiesListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\UnqueueCookiesListener<extended>
 */
class UniqueCookiesListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var \Illuminate\Cookie\CookieJar $cookies */
        $cookies = $this->app->make('cookie');

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $cookies->queue('foo', 'one');
        $cookies->queue('bar', 'two');

        $this->assertNotEmpty($cookies->getQueuedCookies());

        $this->listenerFactory()->handle($event_mock);

        $this->assertEmpty($cookies->getQueuedCookies());
    }

    /**
     * @return UnqueueCookiesListener
     */
    protected function listenerFactory(): UnqueueCookiesListener
    {
        return new UnqueueCookiesListener();
    }
}
