<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\RebindHttpKernelListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindHttpKernelListener
 */
class RebindHttpKernelListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(HttpKernel::class);

        // Set "wrong" app instance in kernel
        $this->setProperty($kernel, 'app', clone $this->app);

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotSame($this->app, $kernel->getApplication());

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $kernel->getApplication());
    }

    /**
     * @return RebindHttpKernelListener
     */
    protected function listenerFactory(): RebindHttpKernelListener
    {
        return new RebindHttpKernelListener();
    }
}
