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
            ->addOption(self::OPTION_RUN_ONCE, 'o', InputOption::VALUE_NONE, 'Loop the projection only once, then exit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $keepRunning = ! $input->getOption(self::OPTION_RUN_ONCE);
        $output->writeln(
            \sprintf(
                '<action>Starting projection <highlight>%s</highlight>. Keep running: <highlight>%s</highlight></action>',
                $this->projectionName,
                $keepRunning === true ? 'enabled' : 'disabled'
            )
        );

        $projector = $this->projection->project($this->projector);
        $projector->run($keepRunning);
        $output->writeln(\sprintf('<action>Projection <highlight>%s</highlight> completed.</action>', $this->projectionName));

        return 0;
    }
}
