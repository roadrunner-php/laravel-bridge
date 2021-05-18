<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spiral\RoadRunnerLaravel\Listeners\ResetLaravelSocialiteListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetLaravelSocialiteListener<extended>
 */
class ResetLaravelSocialiteListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);

        $config->set('services.github', [
            'client_id'     => 'github-client-id',
            'client_secret' => 'github-client-secret',
            'redirect'      => 'http://your-callback-url',
        ]);

        $app_clone = clone $this->app;

        /** @see \Laravel\Socialite\SocialiteServiceProvider::register() */
        $this->app->singleton(SocialiteFactory::class, SocialiteManager::class);

        /** @var \Laravel\Socialite\SocialiteManager $socialite */
        $socialite = $this->app->make(SocialiteFactory::class);

        $this->setProperty($socialite, $app_prop = 'app', $app_clone);
        $this->setProperty($socialite, $container_prop = 'container', $app_clone);

        // burn drivers property
        $socialite->driver('github');

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotEmpty($this->getProperty($socialite, $drivers_prop = 'drivers'));

        $this->listenerFactory()->handle($event_mock);

        $this->assertEmpty($this->getProperty($socialite, $drivers_prop));
        $this->assertSame($this->app, $this->getProperty($socialite, $app_prop));
        $this->assertSame($this->app, $this->getProperty($socialite, $container_prop));
    }

    /**
     * @return ResetLaravelSocialiteListener
     */
    protected function listenerFactory(): ResetLaravelSocialiteListener
    {
        return new ResetLaravelSocialiteListener();
    }
}
