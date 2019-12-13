<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionStreamPositionsCommand extends AbstractProjectionCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('event-store:projection:positions')
            ->setDescription('Shows the current stream positions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<action>All stream positions on this projection manager:</action>');
        $table = (new Table($output))->setHeaders(['Stream', 'Position']);
        foreach ($this->projectionManager->fetchProjectionStreamPositions($this->projectionName) as $stream => $position) {
            $table->addRow([$stream, $position]);
        }
        $table->render();

        return 0;
    }
}
