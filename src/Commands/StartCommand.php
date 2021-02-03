<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    /**
     * Declared options names.
     */
    protected const
        OPTION_SOCKET_TYPE = 'socket-type',
        OPTION_SOCKET_ADDRESS = 'socket-address',
        OPTION_SOCKET_PORT = 'socket-port',
        OPTION_APP_REFRESH = 'app-refresh',
        OPTION_BASE_PATH = 'base-path';

    /**
     * @var string
     */
    protected static $defaultName = 'worker:configure';

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Pre init settings for worker start')
            ->addOption(
                self::OPTION_SOCKET_TYPE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Socket type'
            )
            ->addOption(
                self::OPTION_SOCKET_ADDRESS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Socket addres (ex. localhost, rr.sock)'
            )
            ->addOption(
                self::OPTION_SOCKET_PORT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Socket port'
            );

        $this
            ->addOption(
                self::OPTION_APP_REFRESH,
                null,
                InputOption::VALUE_OPTIONAL,
                'App refresh (ex. true, false)'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $base_path = $input->getOption(self::OPTION_BASE_PATH);

        if (!\is_string($base_path)) {
            throw new \InvalidArgumentException('Option ' . self::OPTION_BASE_PATH . ' must be string type.');
        }

        (new \Spiral\RoadRunnerLaravel\Worker($base_path))
            ->start((bool) $input->getOption(self::OPTION_APP_REFRESH));

        return 0;
    }
}
