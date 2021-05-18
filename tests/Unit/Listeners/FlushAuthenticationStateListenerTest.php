<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Support\Str;
use Illuminate\Cache\CacheManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\FlushAuthenticationStateListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushAuthenticationStateListener<extended>
 */
class FlushAuthenticationStateListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;

        /** @var \Illuminate\Auth\AuthManager $auth */
        $auth = $this->app->make('auth');

        $this->setProperty($auth, $app_prop = 'app', $app_clone);

        // burn guards array
        $auth->guard($auth->getDefaultDriver());

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotEmpty($this->getProperty($auth, $guards_prop = 'guards'));

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($auth, $app_prop));
        $this->assertEmpty($this->getProperty($auth, $guards_prop));
    }

    /**
     * @return FlushAuthenticationStateListener
     */
    protected function listenerFactory(): FlushAuthenticationStateListener
    {
        return new FlushAuthenticationStateListener();
    }
}
