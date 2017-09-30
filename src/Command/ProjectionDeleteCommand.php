<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionDeleteCommand extends AbstractProjectionCommand
{
    protected const OPTION_WITH_EVENTS = 'with-emitted-events';

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('event-store:projection:delete')
            ->setDescription('Delete a projection')
            ->addOption(static::OPTION_WITH_EVENTS, 'w', InputOption::VALUE_NONE, 'Delete with emitted events');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $withEvents = $input->getOption(self::OPTION_WITH_EVENTS);
        if ($withEvents) {
            $output->writeln(sprintf('<action>Deleting projection <highlight>%s</highlight> with emitted events</action>', $this->projectionName));
        } else {
            $output->writeln(sprintf('<action>Deleting projection </action><highlight>%s</highlight>', $this->projectionName));
        }
        if ($this->readModel) {
            $this->readModel->delete();
        }
        $this->projectionManager->deleteProjection($this->projectionName, $withEvents);
    }
}
