<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Mockery as m;
use Illuminate\Database\DatabaseManager;
use Illuminate\Config\Repository as ConfigRepository;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\ResetDbConnectionsListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetDbConnectionsListener<extended>
 */
class ResetDbConnectionsListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        /** @var DatabaseManager $db_manager */
        $db_manager = $this->app->make('db');

        $config->set('database.default', $connection_name = 'sqlite');
        $config->set("database.connections.{$connection_name}", [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $db_manager->connection($connection_name)->reconnect();

        $this->assertInstanceOf(
            \PDO::class,
            $db_manager->connection($connection_name)->getPdo(),
            'DB not connected'
        );

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertNull($db_manager->connection($connection_name)->getPdo(), 'DB not disconnected');
    }

    /**
     * @return ResetDbConnectionsListener
     */
    protected function listenerFactory(): ResetDbConnectionsListener
    {
        return new ResetDbConnectionsListener();
    }
}
