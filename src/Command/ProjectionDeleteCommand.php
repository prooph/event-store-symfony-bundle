<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionDeleteCommand extends AbstractProjectionCommand
{
    protected const OPTION_WITH_EVENTS = 'with-emitted-events';

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('event-store:projection:delete')
            ->setDescription('Delete a projection')
            ->addOption(static::OPTION_WITH_EVENTS, 'w', InputOption::VALUE_NONE, 'Delete with emitted events');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $withEvents = $input->getOption(self::OPTION_WITH_EVENTS);

        $message = \sprintf(
            '<action>Deleting projection <highlight>%s</highlight>%s</action>',
            $this->projectionName,
            $withEvents ? ' with emitted events' : ' without emitted events',
        );
        $output->writeln($message);

        $this->projectionManager->deleteProjection($this->projectionName, $withEvents);

        return 0;
    }
}
