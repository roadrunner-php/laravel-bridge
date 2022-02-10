<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Spiral\RoadRunnerLaravel\Listeners\CleanupUploadedFilesListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\CleanupUploadedFilesListener
 */
class CleanupUploadedFilesListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $tmp_dir = $this->createTemporaryDirectory();

        $this->assertNotFalse(
            \file_put_contents(
                $file_1_path = $tmp_dir . DIRECTORY_SEPARATOR . ($file_1_name = Str::random()),
                Str::random(),
            )
        );

        $this->assertNotFalse(
            \file_put_contents(
                $file_2_path = $tmp_dir . DIRECTORY_SEPARATOR . ($file_2_name = Str::random()),
                Str::random(),
            )
        );

        $this->assertNotFalse(
            \file_put_contents(
                $file_3_path = $tmp_dir . DIRECTORY_SEPARATOR . ($file_3_name = Str::random()),
                Str::random(),
            )
        );

        $request = Request::create('http://127.0.0.1:123/foo');

        $request->files->add([
            new UploadedFile($file_1_path, $file_1_name),
            new UploadedFile($file_2_path, $file_2_name),
            new UploadedFile($file_3_path, $file_3_name),
        ]);

        \rename($file_3_path, $file_3_new_path = $file_3_path . Str::random());

        /** @var m\MockInterface|WithHttpRequest $event */
        $event = m::mock(WithHttpRequest::class)
            ->makePartial()
            ->expects('httpRequest')
            ->atLeast()
            ->once()
            ->andReturn($request)
            ->getMock();

        $this->assertFileExists($file_1_path);
        $this->assertFileExists($file_2_path);
        $this->assertFileExists($file_3_new_path);

        $this->listenerFactory()->handle($event);

        $this->assertFileDoesNotExist($file_1_path);
        $this->assertFileDoesNotExist($file_2_path);
        $this->assertFileExists($file_3_new_path); // still exists
    }

    /**
     * @return CleanupUploadedFilesListener
     */
    protected function listenerFactory(): CleanupUploadedFilesListener
    {
        return new CleanupUploadedFilesListener();
    }
}
