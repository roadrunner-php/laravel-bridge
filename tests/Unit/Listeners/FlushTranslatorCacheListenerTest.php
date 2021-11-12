<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Translation\Translator;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\FlushTranslatorCacheListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushTranslatorCacheListener
 */
class FlushTranslatorCacheListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var Translator $translator */
        $translator = $this->app->make('translator');

        $translator->setParsedKey('foo::bar.baz', ['foo', 'bar', 'baz']);

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotEmpty($this->getProperty($translator, 'parsed'));

        $this->listenerFactory()->handle($event_mock);

        $this->assertEmpty($this->getProperty($translator, 'parsed'));
    }

    /**
     * @return FlushTranslatorCacheListener
     */
    protected function listenerFactory(): FlushTranslatorCacheListener
    {
        return new FlushTranslatorCacheListener();
    }
}
