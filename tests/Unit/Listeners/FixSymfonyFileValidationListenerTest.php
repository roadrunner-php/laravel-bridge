<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Spiral\RoadRunnerLaravel\Listeners\FixSymfonyFileValidationListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FixSymfonyFileValidationListener<extended>
 */
class FixSymfonyFileValidationListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $function_location = '\\Symfony\\Component\\HttpFoundation\\File\\is_uploaded_file';

        $this->assertFalse(\function_exists($function_location));
        $this->assertFalse(\is_uploaded_file('foo'));

        $this->listenerFactory()->handle(new \stdClass());

        $this->assertTrue(\function_exists($function_location));
        $this->assertTrue($function_location('foo'));
    }

    /**
     * @return FixSymfonyFileValidationListener
     */
    protected function listenerFactory(): FixSymfonyFileValidationListener
    {
        return new FixSymfonyFileValidationListener();
    }
}
