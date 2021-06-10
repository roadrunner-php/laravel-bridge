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
        $old_file_path = $tmp_dir . DIRECTORY_SEPARATOR . Str::random();
        $new_file_path = $tmp_dir . DIRECTORY_SEPARATOR . Str::random();
        \file_put_contents($old_file_path, '');
        $this->assertFileExists($old_file_path);
        $this->assertTrue($function_location($old_file_path, $new_file_path));
        $this->assertFileExists($new_file_path);
        $this->assertFileNotExists($old_file_path);
        $rnd_file_path1 = $tmp_dir . DIRECTORY_SEPARATOR . Str::random();
        $rnd_file_path2 = $tmp_dir . DIRECTORY_SEPARATOR . Str::random();
        $this->assertFalse($function_location($rnd_file_path1, $rnd_file_path2));
    }

    /**
     * @return FixSymfonyFileMovingListener
     */
    protected function listenerFactory(): FixSymfonyFileMovingListener
    {
        return new FixSymfonyFileMovingListener();
    }
}
