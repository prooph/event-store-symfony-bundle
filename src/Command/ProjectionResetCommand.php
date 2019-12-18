<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionResetCommand extends AbstractProjectionCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('event-store:projection:reset')
            ->setDescription('Resets a projection');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(\sprintf('<action>Resetting projection <highlight>%s</highlight></action>', $this->projectionName));
        $this->projectionManager->resetProjection($this->projectionName);

        return 0;
    }
}
