<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Routing\UrlGenerator;
use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * This listener must be registered BEFORE `SetServerPortListener` for correct links generation.
 *
 * @see SetServerPortListener
 */
class ForceHttpsListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithHttpRequest && $event instanceof WithApplication) {
            $app = $event->application();

            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            if ((bool) $config->get(ServiceProvider::getConfigRootKey() . '.force_https', false)) {
                /** @var UrlGenerator $url_generator */
                $url_generator = $app->make(UrlGenerator::class);

                $url_generator->forceScheme('https');

                // Set 'HTTPS' server parameter (required for correct working request methods like ::isSecure and others)
                $event->httpRequest()->server->set('HTTPS', 'on');
            }
        }
    }
}
