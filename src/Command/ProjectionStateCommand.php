<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionStateCommand extends AbstractProjectionCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('event-store:projection:state')
            ->setDescription('Shows the current projection state');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<action>Current state:</action>');
        $output->writeln(json_encode($this->projectionManager->fetchProjectionState($this->projectionName)));
    }
}
