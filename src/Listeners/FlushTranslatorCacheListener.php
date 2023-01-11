<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Translation\Translator;
use Illuminate\Support\NamespacedItemResolver;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/pull/416/files
 */
class FlushTranslatorCacheListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (! $app->resolved($translator_abstract = 'translator')) {
                return;
            }

            /** @var Translator $translator */
            $translator = $app->make($translator_abstract);

            if ($translator instanceof NamespacedItemResolver) {
                $translator->flushParsedKeys();
            }
        }
    }
}
