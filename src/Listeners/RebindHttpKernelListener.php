<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/RebindKernelContainer.php
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToHttpKernel.php
 */
class RebindHttpKernelListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($kernel_abstract = HttpKernel::class)) {
                return;
            }

            /** @var \Illuminate\Foundation\Http\Kernel $kernel */
            $kernel = $app->make($kernel_abstract);

            $kernel->setApplication($app);
        }
    }
}
