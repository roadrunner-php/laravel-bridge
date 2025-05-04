<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Spiral\RoadRunner\Environment;

class WorkerFactory
{
    /**
     * @var string Depends on the environment
     */
    public const MODE_AUTO = 'auto';

    public function __construct(
        protected string $appBasePath,
    ) {}

    /**
     * @param mixed ...$args
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function make(string $mode = self::MODE_AUTO, ...$args): WorkerInterface
    {
        if ($mode === self::MODE_AUTO) {
            $mode = Environment::fromGlobals()->getMode();
        }

        if (\array_key_exists($mode, $map = $this->getWorkersMap())) {
            $class = $map[$mode];
            $worker = new $class(...$args);

            if ($worker instanceof WorkerInterface) {
                return $worker;
            }

            throw new \RuntimeException(
                \sprintf("Class [{$class}] should implements [%s] interface", WorkerInterface::class),
            );
        }

        throw new \InvalidArgumentException("Unsupported worker mode: {$mode}");
    }

    /**
     * @return array<string, class-string<WorkerInterface>>
     */
    protected function getWorkersMap(): array
    {
        $map = ((array) require ServiceProvider::getConfigPath())[$key = 'workers']; // load defaults

        if (\file_exists($path = $this->appBasePath . '/config/' . ServiceProvider::getConfigRootKey() . '.php')) {
            if (\array_key_exists($key, $userDefined = (array) require $path)) {
                if (\is_array($userDefined[$key])) {
                    $map = \array_merge($map, $userDefined[$key]);
                }
            }
        }

        return $map;
    }
}
