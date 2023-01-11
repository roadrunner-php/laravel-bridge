<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Mail\MailManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToMailManager.php
 */
class RebindMailManagerListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($mail_manager_abstract = 'mail.manager')) {
                return;
            }

            /** @var MailManager $mail_manager */
            $mail_manager = $app->make($mail_manager_abstract);

            $mail_manager->setApplication($app);
            $mail_manager->forgetMailers();
        }
    }
}
