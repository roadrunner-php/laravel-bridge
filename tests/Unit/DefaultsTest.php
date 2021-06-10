<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit;

use Spiral\RoadRunnerLaravel\Defaults;

/**
 * @covers \Spiral\RoadRunnerLaravel\Defaults
 */
class DefaultsTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testEventListeners(): void
    {
        $defined = [];

        foreach ([
                     Defaults::beforeLoopStarted(),
                     Defaults::beforeLoopIteration(),
                     Defaults::beforeRequestHandling(),
                     Defaults::afterRequestHandling(),
                     Defaults::afterLoopIteration(),
                     Defaults::afterLoopStopped(),
                     Defaults::loopErrorOccurred(),
                 ] as $list) {
            foreach ($list as $listener_class) {
                $this->assertTrue(\class_exists($listener_class));

                $this->assertNotContains($listener_class, $defined);
                $defined[] = $listener_class;
            }
        }
    }

    /**
     * @return void
     */
    public function testWarmAndClear(): void
    {
        foreach ([...Defaults::servicesToWarm(), ...Defaults::servicesToClear()] as $item) {
            $this->assertIsString($item);
            $this->assertNotEmpty($item);
        }
    }

    /**
     * @return void
     */
    public function testResetProviders(): void
    {
        if (!empty($providers = Defaults::providersToReset())) {
            foreach ($providers as $item) {
                $this->assertTrue(\class_exists($item));
            }
        } else {
            $this->markTestSkipped('Providers was not defined');
        }
    }
}
