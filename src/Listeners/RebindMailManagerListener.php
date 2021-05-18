<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

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

            /** @var \Illuminate\Mail\MailManager $mail_manager */
            $mail_manager = $app->make($mail_manager_abstract);

            /**
             * Method `setApplication` for the MailManager available since Laravel v8.35.0.
             *
             * @link https://git.io/JszC5 Source code (v8.35.0)
             * @see  \Illuminate\Mail\MailManager::setApplication
             */
            if (! $this->invokeMethod($mail_manager, 'setApplication', $app)) {
                $this->setProperty($mail_manager, 'app', $app);
            }

            /**
             * Method `forgetMailers` for the MailManager available since Laravel v8.35.0.
             *
             * @link https://git.io/JszWd Source code (v8.35.0)
             * @see  \Illuminate\Mail\MailManager::forgetMailers
             */
            if (! $this->invokeMethod($mail_manager, 'forgetMailers')) {
                $this->setProperty($mail_manager, 'mailers', []);
            }
        }
    }
}
