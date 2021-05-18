<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\RebindMailManagerListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindMailManagerListener<extended>
 */
class RebindMailManagerListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;

        /** @var \Illuminate\Mail\MailManager $mail_manager */
        $mail_manager = $this->app->make('mail.manager');

        $this->setProperty($mail_manager, $app_prop = 'app', $app_clone);

        // burn 'mailers' property
        $mail_manager->driver($mail_manager->getDefaultDriver());

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotEmpty($this->getProperty($mail_manager, $mailers_prop = 'mailers'));

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($mail_manager, $app_prop));
        $this->assertEmpty($this->getProperty($mail_manager, $mailers_prop));
    }

    /**
     * @return RebindMailManagerListener
     */
    protected function listenerFactory(): RebindMailManagerListener
    {
        return new RebindMailManagerListener();
    }
}
