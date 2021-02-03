<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Commands;

use Spiral\RoadRunnerLaravel\RunParams;
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
                'Socket type. Default: null'
            )
            ->addOption(
                self::OPTION_SOCKET_ADDRESS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Socket address (ex. localhost, rr.sock). Default: null'
            )
            ->addOption(
                self::OPTION_SOCKET_PORT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Socket port Default: null',
                null
            );

        $this
            ->addOption(
                self::OPTION_APP_REFRESH,
                null,
                InputOption::VALUE_OPTIONAL,
                'App refresh (ex. true, false)',
                true
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
        $base_path = $this->validateBasePath($input->getOption(self::OPTION_BASE_PATH));

        $run_params = $this->initRunParamsFromInput($input);

        (new \Spiral\RoadRunnerLaravel\Worker($base_path))
            ->start($run_params->isAppRefresh(), $run_params);

        return 0;
    }

    /**
     * @param string|string[]|bool|null $base_path
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function validateBasePath($base_path): string
    {
        if (!\is_string($base_path)) {
            throw new \InvalidArgumentException('Option ' . self::OPTION_BASE_PATH . ' must be string type.');
        }

        return $base_path;
    }

    /**
     * @param InputInterface $input
     *
     * @return RunParams
     */
    protected function initRunParamsFromInput(InputInterface $input)
    {
        return (new RunParams())
            ->setAppRefresh((bool) $input->getOption(self::OPTION_APP_REFRESH))
            ->setSocketAddress($input->getOption(self::OPTION_SOCKET_ADDRESS))
            ->setSocketType($input->getOption(self::OPTION_SOCKET_TYPE))
            ->setSocketPort($input->getOption(self::OPTION_SOCKET_PORT));
    }
}
