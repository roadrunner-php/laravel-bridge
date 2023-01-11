<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Contracts\Translation\Translator;
use Carbon\Laravel\ServiceProvider as CarbonServiceProvider;
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

            if (!$app->bound($translator_abstract = 'translator')) {
                return;
            }

            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            /** @var Translator $translator */
            $translator = $app->make($translator_abstract);

            if (\is_string($app_locale = $config->get('app.locale'))) {
                $translator->setLocale($app_locale);
            }

            if (\is_string($app_fallback_locale = $config->get('app.fallback_locale'))) {
                // method `setFallback` is not defined in the translator contract
                $this->invokeMethod($translator, 'setFallback', $app_fallback_locale);
            }

            (new CarbonServiceProvider($app))->updateLocale();
        }
    }
}
