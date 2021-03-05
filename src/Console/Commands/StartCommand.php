<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Console\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class StartCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * Option names.
     */
    protected const
        OPTION_LARAVEL_PATH = 'laravel-path',
        OPTION_RELAY_DSN = 'relay-dsn',
        OPTION_REFRESH_APP = 'refresh-app';

    /**
     * @var string|null
     */
    protected $laravel_base_path;

    /**
     * Create a new command instance.
     *
     * @var string|null $laravel_base_path
     */
    public function __construct(?string $laravel_base_path = null)
    {
        parent::__construct();

        $this->laravel_base_path = $laravel_base_path;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('start');
        $this->setDescription('Start RoadRunner worker');

        $this->addOption(
            static::OPTION_LARAVEL_PATH,
            null,
            InputOption::VALUE_OPTIONAL,
            'Laravel application base path (optional)'
        );

        $this->addOption(
            static::OPTION_RELAY_DSN,
            null,
            InputOption::VALUE_REQUIRED,
            'Relay DSN (eg.: <comment>' . \implode('</comment>, <comment>', ['pipes', 'tcp://localhost:6001', 'unix:///tmp/rpc.sock']) . '</comment>)',
            'pipes'
        );

        $this->addOption(
            static::OPTION_REFRESH_APP,
            null,
            InputOption::VALUE_NONE,
            'Refresh application instance on each HTTP request (avoid this for performance reasons)'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new \Spiral\RoadRunnerLaravel\Worker)->start(new \Spiral\RoadRunnerLaravel\WorkerOptions(
            $this->getLaravelBasePath($input),
            $this->getRefreshApp($input),
            $this->getRelayDSN($input),
        ));

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function getLaravelBasePath(InputInterface $input): string
    {
        $base_path = $input->getOption(static::OPTION_LARAVEL_PATH);

        if (\is_string($base_path) && !empty($base_path)) {
            return $base_path;
        }

        if (\is_string($this->laravel_base_path) && !empty($this->laravel_base_path)) {
            return $this->laravel_base_path;
        }

        throw new InvalidArgumentException("Laravel base path was not set");
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function getRelayDSN(InputInterface $input): string
    {
        $relay_dsn = $input->getOption(static::OPTION_RELAY_DSN);

        if (\is_string($relay_dsn) && !empty($relay_dsn)) {
            return $relay_dsn;
        }

        throw new InvalidArgumentException("Invalid option value for relay DSN");
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    protected function getRefreshApp(InputInterface $input): bool
    {
        $refresh_app = $input->getOption(static::OPTION_REFRESH_APP);

        if (\is_bool($refresh_app)) {
            return $refresh_app;
        }

        throw new InvalidArgumentException("Invalid option value for app refreshing");
    }
}
