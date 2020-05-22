<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

/**
 * @link https://github.com/avto-dev/roadrunner-laravel/issues/10
 * @link https://github.com/spiral/roadrunner/issues/133
 */
class FixSymfonyFileValidationListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if (!\function_exists('\\Symfony\\Component\\HttpFoundation\\File\\is_uploaded_file')) {
            require __DIR__ . '/../../fixes/fix-symfony-file-validation.php';
        }
    }
}
