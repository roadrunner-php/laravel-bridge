<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Spiral\RoadRunnerLaravel\Listeners\ListenerInterface;

abstract class AbstractListenerTestCase extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testImplementation(): void
    {
        $this->assertInstanceOf(ListenerInterface::class, $this->listenerFactory());
    }

    /**
     * Listener factory.
     *
     * @return ListenerInterface|mixed
     */
    abstract protected function listenerFactory();

    /**
     * Test listener `handle` method.
     *
     * @return void
     */
    abstract protected function testHandle(): void;

    /**
     * @deprecated
     *
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    protected function getProperty($object, string $property)
    {
        $result = null;

        $closure = function () use ($property, &$result): void {
            $result = $this->{$property};
        };

        $getter = $closure->bindTo($object, $object);
        $getter();

        return $result;
    }

    /**
     * @deprecated
     *
     * @param object $object
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     */
    protected function setProperty($object, string $property, $value): void
    {
        $closure = function () use ($property, &$value): void {
            $this->{$property} = $value;
        };

        $setter = $closure->bindTo($object, $object);
        $setter();
    }
}
