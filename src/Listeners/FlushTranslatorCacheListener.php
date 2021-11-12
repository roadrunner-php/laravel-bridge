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
                /**
                 * Method `flushParsedKeys` for the Translator available since Laravel v8.70.0.
                 *
                 * @link https://git.io/JXd0v Source code (v8.70.0)
                 * @see  Translator::flushParsedKeys()
                 */
                if (! $this->invokeMethod($translator, 'flushParsedKeys')) {
                    $this->setProperty($translator, 'parsed', []);
                }
            }
        }
    }
}
