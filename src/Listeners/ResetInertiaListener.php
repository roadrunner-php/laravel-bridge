<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Inertia\ResponseFactory as InertiaResponseFactory;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * Target package: <https://github.com/inertiajs/inertia-laravel>.
 *
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/PrepareInertiaForNextOperation.php
 */
class ResetInertiaListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if (!\class_exists(InertiaResponseFactory::class)) {
            return;
        }

        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($inertia_abstract = InertiaResponseFactory::class)) {
                return;
            }

            /** @var InertiaResponseFactory $inertia */
            $inertia = $app->make($inertia_abstract);

            /** @see \Inertia\ResponseFactory::flushShared() */
            if (!$this->invokeMethod($inertia, 'flushShared')) {
                $this->setProperty($inertia, 'sharedProps', []);
            }
        }
    }
}
