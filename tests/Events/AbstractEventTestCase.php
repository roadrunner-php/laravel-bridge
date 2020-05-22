<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Events;

use Spiral\RoadRunnerLaravel\Tests\AbstractTestCase;

abstract class AbstractEventTestCase extends AbstractTestCase
{
    /**
     * @var string[]
     */
    protected $required_interfaces = [];

    /**
     * @var string|null
     */
    protected $event_class;

    /**
     * @return void
     */
    public function testInterfacesImplementation(): void
    {
        if (empty($this->required_interfaces)) {
            $this->fail('Required interfaces was not defined');
        }

        foreach ($this->required_interfaces as $interface) {
            $this->assertContains(
                $interface,
                \class_implements($this->event_class),
                "Event [{$this->event_class}] does not implements [{$interface}]"
            );
        }
    }

    /**
     * Test event constructor.
     *
     * @return void
     */
    abstract public function testConstructor(): void;
}
