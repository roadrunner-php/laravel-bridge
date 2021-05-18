<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Support\Str;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\ResetDatabaseRecordModificationStateListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetDatabaseRecordModificationStateListener<extended>
 */
class ResetDatabaseRecordModificationStateListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);

        $config->set('database.connections.' . ($connection_name = 'test_' . Str::random()), [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app->make('db')->connection($connection_name);

        $connection->recordsHaveBeenModified(true);
        $this->assertTrue($this->getProperty($connection, 'recordsModified'));

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertFalse($this->getProperty($connection, 'recordsModified'));
    }

    /**
     * @return ResetDatabaseRecordModificationStateListener
     */
    protected function listenerFactory(): ResetDatabaseRecordModificationStateListener
    {
        return new ResetDatabaseRecordModificationStateListener();
    }
}
