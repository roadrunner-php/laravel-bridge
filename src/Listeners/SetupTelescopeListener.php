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

            if (!\class_exists(Telescope::class) || !$config->get('telescope.enabled')) {
                return;
            }

            Telescope::filter(static function (IncomingEntry $entry): bool {
                switch ($entry->type) {
                    case EntryType::EVENT:
                        if (Str::startsWith($entry->content['name'] ?? '', 'Spiral\\RoadRunnerLaravel\\')) {
                            return false;
                        }

                        break;

                    case EntryType::REQUEST:
                        if (Str::startsWith($entry->content['controller_action'] ?? '', 'Laravel\\Telescope\\')) {
                            return false;
                        }

                        break;

                    case EntryType::VIEW:
                        if (Str::startsWith($entry->content['name'] ?? '', 'telescope::')) {
                            return false;
                        }

                        break;

                    case EntryType::REDIS:
                        $cmd = $entry->content['command'] ?? '';

                        if (Str::contains($cmd, ['telescope:pause-recording', 'telescope:dump-watcher'])) {
                            return false;
                        }

                        break;
                }

                return true;
            });
        }
    }
}
