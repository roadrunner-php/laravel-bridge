<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Contracts\Translation\Translator;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushLocaleState.php
 */
class ResetLocaleStateListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if ($app->bound($translator_abstract = 'translator')) {
                /** @var ConfigRepository $config */
                $config = $app->make(ConfigRepository::class);

                /** @var Translator $translator */
                $translator = $app->make($translator_abstract);

                $translator->setLocale($config->get('app.locale'));

                // method `setFallback` is not defined in the translator contract
                $this->invokeMethod($translator, 'setFallback', $config->get('app.fallback_locale'));
            }
        }
    }
}
