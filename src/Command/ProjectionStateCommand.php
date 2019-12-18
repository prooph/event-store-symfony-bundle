<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionStateCommand extends AbstractProjectionCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('event-store:projection:state')
            ->setDescription('Shows the current projection state');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<action>Current state:</action>');

        /** @var string $state */
        $state = \json_encode($this->projectionManager->fetchProjectionState($this->projectionName));
        $output->writeln($state);

        return 0;
    }
}
