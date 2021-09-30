<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Console\Commands;

use Spiral\RoadRunner\Environment;
use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\WorkerInterface;

class WorkerFactory
{
    /**
     * @var string Depends on the environment
     */
    public const MODE_AUTO = 'auto';

    /**
     * Base path to the Laravel application.
     */
    protected string $app_base_path;

    /**
     * @param string $app_base_path
     */
    public function __construct(string $app_base_path)
    {
        $this->app_base_path = $app_base_path;
    }

    /**
     * @param string $mode
     * @param mixed  ...$args
     *
     * @return WorkerInterface
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function make(string $mode = self::MODE_AUTO, ...$args): WorkerInterface
    {
        if ($mode === self::MODE_AUTO) {
            $mode = Environment::fromGlobals()->getMode();

            if ($mode === "") {
                $mode = Environment\Mode::MODE_HTTP; // fallback
            }
        }

        if (\array_key_exists($mode, $map = $this->getWorkersMap())) {
            /** @var string $class */
            $class  = $map[$mode];
            $worker = new $class(...$args);

            if ($worker instanceof WorkerInterface) {
                return $worker;
            }

            throw new \RuntimeException(
                \sprintf("Class [${class}] should implements [%s] interface", WorkerInterface::class)
            );
        }

        throw new \InvalidArgumentException("Unsupported worker mode: ${mode}");
    }

    /**
     * @return array<string, class-string>
     */
    protected function getWorkersMap(): array
    {
        $map = ((array) require ServiceProvider::getConfigPath())[$key = 'workers']; // load defaults

        if (\file_exists($path = $this->app_base_path . '/config/' . ServiceProvider::getConfigRootKey() . '.php')) {
            if (\array_key_exists($key, $user_defined = (array) require $path)) {
                if (\is_array($user_defined[$key])) {
                    $map = \array_merge($map, $user_defined[$key]);
                }
            }
        }

        return $map;
    }
}
