<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Support\Str;
use Illuminate\Contracts\Translation\Translator;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spiral\RoadRunnerLaravel\Listeners\ResetLocaleStateListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushLocaleStateListener<extended>
 */
class ResetLocaleStateListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);

        /** @var Translator $translator */
        $translator = $this->app->make('translator');

        $translator->setLocale($locale = Str::random());
        $translator->setFallback($fallback = Str::random());

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertSame($locale, $translator->getLocale());
        $this->assertSame($fallback, $translator->getFallback());

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($config->get('app.locale'), $translator->getLocale());
        $this->assertSame($config->get('app.fallback_locale'), $translator->getFallback());
    }

    /**
     * @return ResetLocaleStateListener
     */
    protected function listenerFactory(): ResetLocaleStateListener
    {
        return new ResetLocaleStateListener();
    }
}
