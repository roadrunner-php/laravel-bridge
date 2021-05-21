<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

/**
 * @internal
 */
final class ApplicationFactory
{
    /**
     * @param string $base_path
     * @param bool   $boostrap_app
     *
     * @return ApplicationContract
     * @throws BindingResolutionException
     */
    public function create(string $base_path, bool $boostrap_app = true): ApplicationContract
    {
        $path = \implode(\DIRECTORY_SEPARATOR, [\rtrim($base_path, \DIRECTORY_SEPARATOR), 'bootstrap', 'app.php']);

        if (!\is_file($path)) {
            throw new \InvalidArgumentException("Application bootstrap file was not found in [{$path}]");
        }

        /** @var ApplicationContract $app */
        $app = require $path;

        if ($boostrap_app) {
            $this->bootstrap($app);
        }

        return $app;
    }

    /**
     * @param ApplicationContract $app
     *
     * @throws BindingResolutionException
     */
    protected function bootstrap(ApplicationContract $app): void
    {
        /** @var \Illuminate\Foundation\Http\Kernel $http_kernel */
        $http_kernel = $app->make(HttpKernelContract::class);

        $bootstrappers = $this->getKernelBootstrappers($http_kernel);

        // insert `SetRequestForConsole` bootstrapper before `RegisterProviders` if it does not exists
        if (!\in_array(SetRequestForConsole::class, $bootstrappers, true)) {
            $register_index = \array_search(RegisterProviders::class, $bootstrappers, true);

            if (\is_int($register_index)) {
                \array_splice($bootstrappers, $register_index, 0, [SetRequestForConsole::class]);
            }
        }

        $app->bootstrapWith($bootstrappers);
    }

    /**
     * Get HTTP or Console kernel bootstrappers.
     *
     * @param HttpKernel|ConsoleKernel $kernel
     *
     * @return array<class-string> Bootstrappers class names
     */
    protected function getKernelBootstrappers($kernel): array
    {
        $bootstrappers = [];

        \Closure::fromCallable(function () use (&$bootstrappers): void {
            /**
             * @see HttpKernel::bootstrappers()
             * @see ConsoleKernel::bootstrappers()
             *
             * @var HttpKernel|ConsoleKernel $this
             */
            \array_push($bootstrappers, ...$this->bootstrappers());
        })->bindTo($kernel, $kernel)();

        return $bootstrappers;
    }
}
