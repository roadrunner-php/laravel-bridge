<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Support\Str;
use Spiral\RoadRunnerLaravel\Listeners\CloneConfigListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\CloneConfigListener<extended>
 */
class CloneConfigListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var ConfigRepository $original_config */
        $original_config = $this->app->make(ConfigRepository::class);
        $original_config->set('foo', $foo = Str::random());

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertNotSame($original_config, $this->app->make(ConfigRepository::class));
        $this->assertSame($foo, $this->app->make('config')->get('foo'));
    }

    /**
     * @return CloneConfigListener
     */
    protected function listenerFactory(): CloneConfigListener
    {
        return new CloneConfigListener();
    }
}
