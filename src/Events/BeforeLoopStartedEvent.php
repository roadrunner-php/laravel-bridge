<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Events;

use Illuminate\Contracts\Foundation\Application as ApplicationContract;

final class BeforeLoopStartedEvent implements Contracts\WithApplication
{
    /**
     * Application instance.
     */
    private ApplicationContract $app;

    /**
     * Create a new event instance.
     *
     * @param ApplicationContract $app
     */
    public function __construct(ApplicationContract $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function application(): ApplicationContract
    {
        return $this->app;
    }
}
