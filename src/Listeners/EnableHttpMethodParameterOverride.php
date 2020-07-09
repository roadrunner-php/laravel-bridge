<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;

/**
 * This listener must be registered BEFORE `RebindRouterListener` if you need to process
 * the hidden `_hidden` field when submitted using the HTML form.
 *
 * @see RebindRouterListener
 */
class EnableHttpMethodParameterOverride implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithHttpRequest) {
            $event->httpRequest()->enableHttpMethodParameterOverride();
        }
    }
}
