<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2024 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2024 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionStopCommand extends AbstractProjectionCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('event-store:projection:stop')
            ->setDescription('Stops a projection');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\sprintf('<action>Stopping projection <highlight>%s</highlight></action>', $this->projectionName));
        $this->projectionManager->stopProjection($this->projectionName);

        return 0;
    }
}
