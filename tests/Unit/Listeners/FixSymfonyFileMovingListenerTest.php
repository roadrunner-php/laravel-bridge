<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Illuminate\Support\Str;
use Spiral\RoadRunnerLaravel\Listeners\FixSymfonyFileMovingListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FixSymfonyFileMovingListener
 */
class FixSymfonyFileMovingListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $function_location = '\\Symfony\\Component\\HttpFoundation\\File\\move_uploaded_file';

        $this->assertFalse(\function_exists($function_location));
        $this->assertFalse(\is_uploaded_file('foo'));

        $this->listenerFactory()->handle(new \stdClass());

        $this->assertTrue(\function_exists($function_location));

        $tmp_dir = $this->createTemporaryDirectory();
        \file_put_contents($old_file_path = $tmp_dir . DIRECTORY_SEPARATOR . Str::random(), '');
        $this->assertTrue($function_location($old_file_path, $new_file_path = $tmp_dir . DIRECTORY_SEPARATOR . Str::random()));
        $this->assertFalse($function_location($tmp_dir . DIRECTORY_SEPARATOR . Str::random(), $tmp_dir . DIRECTORY_SEPARATOR . Str::random()));
        $this->assertFileExists($new_file_path);
        $this->assertFileDoesNotExist($old_file_path);
    }

    /**
     * @return FixSymfonyFileMovingListener
     */
    protected function listenerFactory(): FixSymfonyFileMovingListener
    {
        return new FixSymfonyFileMovingListener();
    }
}
