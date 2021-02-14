<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests;

use Spiral\RoadRunnerLaravel\RunParams;

/**
 * @covers \Spiral\RoadRunnerLaravel\RunParams<extended>
 */
class RunParamsTest extends AbstractTestCase
{
    /**
     * @var RunParams
     */
    protected $run_params;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->run_params = new RunParams();
    }

    /**
     * @return void
     */
    public function testAppRefresh(): void
    {
        foreach ([false, true] as $value) {
            $this->run_params->setAppRefresh($value);
            $this->assertSame($value, $this->run_params->isAppRefresh());
        }
    }

    /**
     * @return void
     */
    public function testSocketType(): void
    {
        $this->run_params->setSocketType($value = 'string');
        $this->assertSame($value, $this->run_params->getSocketType());

        foreach (
            [
                     123,
                     null,
                     ['foo', 'bar'],
                        function (): void {
                        },
                 ] as $value
        ) {
            $this->run_params->setSocketType($value);
            $this->assertNull($this->run_params->getSocketType());
        }
    }

    /**
     * @return void
     */
    public function testSocketAddress(): void
    {
        $this->run_params->setSocketAddress($value = 'string');
        $this->assertSame($value, $this->run_params->getSocketAddress());

        foreach (
            [
                     123,
                     null,
                     ['foo', 'bar'],
                        function (): void {
                        },
                 ] as $value
        ) {
            $this->run_params->setSocketAddress($value);
            $this->assertNull($this->run_params->getSocketAddress());
        }
    }

    /**
     * @return void
     */
    public function testSocketPort(): void
    {
        $this->run_params->setSocketPort($value = 'string');
        $this->assertSame((int) $value, $this->run_params->getSocketPort());

        foreach (
            [
                     123,
                     null,
                     ['foo', 'bar'],
                        function (): void {
                        },
                 ] as $value
        ) {
            $this->run_params->setSocketPort($value);
            $this->assertNull($this->run_params->getSocketPort());
        }
    }

    /**
     * @return void
     */
    public function testBasePath(): void
    {
        $this->assertSame('', $this->run_params->getBasePath());

        $this->run_params->setBasePath($value = 'string');
        $this->assertSame($value, $this->run_params->getBasePath());
    }
}
