<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Support\Str;
use Laravel\Telescope\{Telescope, EntryType, IncomingEntry};
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Target package: <https://github.com/laravel/telescope>.
 */
class SetupTelescopeListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            /** @var ConfigRepository $config */
            $config = $event->application()->make(ConfigRepository::class);

            if (!$config->get('telescope.enabled')) {
                return;
            }

            Telescope::filter(static function (IncomingEntry $entry): bool {
                if ($entry->type === EntryType::EVENT) { // ignore current package events
                    if (Str::startsWith(($entry->content['name'] ?? ''), 'Spiral\\RoadRunnerLaravel\\')) {
                        return false;
                    }
                }

                if ($entry->type === EntryType::REQUEST) { // ignore telescope HTTP requests
                    if (Str::startsWith(($entry->content['controller_action'] ?? ''), 'Laravel\\Telescope\\')) {
                        return false;
                    }
                }

                return true;
            });
        }
    }
}
