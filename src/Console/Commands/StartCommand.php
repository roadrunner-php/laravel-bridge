<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Console\Commands;

use Spiral\RoadRunnerLaravel\WorkerFactory;
use Spiral\RoadRunnerLaravel\WorkerOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
class StartCommand extends Command
{
    /**
     * @var string Worker mode option
     */
    protected const OPTION_WORKER_MODE = 'worker-mode';

    /**
     * @var string Laravel base path option
     */
    protected const OPTION_LARAVEL_PATH = 'laravel-path';

    /**
     * @var string Relay DSN option
     */
    protected const OPTION_RELAY_DSN = 'relay-dsn';

    public function __construct(
        protected readonly ?string $laravelBasePath = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('start');
        $this->setDescription('Start RoadRunner worker');

        $this->addOption(
            static::OPTION_LARAVEL_PATH,
            null,
            InputOption::VALUE_OPTIONAL,
            'Laravel application base path (optional)',
        );

        $this->addOption(
            static::OPTION_RELAY_DSN,
            null,
            InputOption::VALUE_REQUIRED,
            'Relay DSN (eg.: <comment>' . \implode(
                '</comment>, <comment>',
                ['pipes', 'tcp://localhost:6001', 'unix:///tmp/relay.sock'],
            ) . '</comment>)',
            'pipes',
            suggestedValues: [
                'pipes',
                'tcp://localhost:6001',
                'unix:///tmp/relay.sock',
            ],
        );

        $this->addOption(
            static::OPTION_WORKER_MODE,
            null,
            InputOption::VALUE_REQUIRED,
            'Worker mode',
            /** @see \Spiral\RoadRunner\Environment\Mode */
            WorkerFactory::MODE_AUTO,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = new WorkerOptions(
            $this->getLaravelBasePath($input),
            $this->getRelayDSN($input),
        );

        $worker = (new WorkerFactory(
            $options->getAppBasePath(),
        ))->make($mode = $this->getWorkerMode($input));

        if ($output->isDebug()) {
            $hints = [
                'Laravel base path' => $options->getAppBasePath(),
                'Relay DSN' => $options->getRelayDsn(),
                'Mode' => $mode,
                'Worker class' => $worker::class,
            ];

            foreach ($hints as $key => $value) {
                $output->writeln(\sprintf('%s: <comment>%s</comment>', $key, $value));
            }
        }

        $worker->start($options);

        return 0;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getLaravelBasePath(InputInterface $input): string
    {
        $basePath = $input->getOption(static::OPTION_LARAVEL_PATH);

        if (\is_string($basePath) && !empty($basePath)) {
            return $basePath;
        }

        if (\is_string($this->laravelBasePath) && !empty($this->laravelBasePath)) {
            return $this->laravelBasePath;
        }

        throw new \InvalidArgumentException("Laravel base path was not set");
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getWorkerMode(InputInterface $input): string
    {
        $workerMode = $input->getOption(static::OPTION_WORKER_MODE);

        if (\is_string($workerMode) && !empty($workerMode)) {
            return $workerMode;
        }

        throw new \InvalidArgumentException("Invalid option value for the worker mode");
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getRelayDSN(InputInterface $input): string
    {
        $relayDsn = $input->getOption(static::OPTION_RELAY_DSN);

        if (\is_string($relayDsn) && !empty($relayDsn)) {
            return $relayDsn;
        }

        throw new \InvalidArgumentException("Invalid option value for relay DSN");
    }
}
