<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * Target package: <https://github.com/laravel/socialite/>.
 *
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/PrepareSocialiteForNextOperation.php
 */
class ResetLaravelSocialiteListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if (!\class_exists(\Laravel\Socialite\SocialiteManager::class)) {
            return;
        }

        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($socialite_abstract = SocialiteFactory::class)) {
                return;
            }

            /** @var SocialiteFactory $socialite */
            $socialite = $app->make($socialite_abstract);

            /** @see \Laravel\Socialite\SocialiteManager::forgetDrivers() */
            if (!$this->invokeMethod($socialite, 'forgetDrivers')) {
                $this->setProperty($socialite, 'drivers', []);
            }

            /** @see \Laravel\Socialite\SocialiteManager::setContainer() */
            if (!$this->invokeMethod($socialite, 'setContainer', $app)) {
                $this->setProperty($socialite, 'app', $app);
                $this->setProperty($socialite, 'container', $app);
            }
        }
    }
}
