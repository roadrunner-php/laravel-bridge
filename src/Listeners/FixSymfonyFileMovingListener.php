<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

/**
 * @link https://github.com/roadrunner-php/laravel-bridge/issues/43
 */
class FixSymfonyFileMovingListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if (!\function_exists('\\Symfony\\Component\\HttpFoundation\\File\\move_uploaded_file')) {
            require __DIR__ . '/../../fixes/fix-symfony-file-moving.php';
        }
    }
}
