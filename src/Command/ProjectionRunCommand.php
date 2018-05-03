<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionRunCommand extends AbstractProjectionCommand
{
    private const OPTION_RUN_ONCE = 'run-once';

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('event-store:projection:run')
            ->setDescription('Runs a projection')
            ->addOption(static::OPTION_RUN_ONCE, 'o', InputOption::VALUE_NONE, 'Loop the projection only once, then exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keepRunning = ! $input->getOption(static::OPTION_RUN_ONCE);
        $output->writeln(
            sprintf(
                '<action>Starting projection <highlight>%s</highlight>. Keep running: <highlight>%s</highlight></action>', $this->projectionName,
                $keepRunning === true ? 'enabled' : 'disabled'
            )
        );

        $projector = $this->projection->project($this->projector);
        $projector->run($keepRunning);
        $output->writeln(sprintf('<action>Projection <highlight>%s</highlight> completed.</action>', $this->projectionName));
    }
}
