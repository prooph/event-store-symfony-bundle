<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionNamesCommand extends AbstractProjectionCommand
{
    private const ARGUMENT_FILTER = 'filter';
    private const OPTION_REGEX = 'regex';
    private const OPTION_LIMIT = 'limit';
    private const OPTION_OFFSET = 'offset';

    protected function configure()
    {        
        $this
            ->setName('event-store:projection:names')
            ->setDescription('Shows a list of all projection names. Can be filtered.')
            ->addArgument(self::ARGUMENT_FILTER, InputArgument::OPTIONAL, 'Filter by this string')
            ->addOption(self::OPTION_REGEX, 'r', InputOption::VALUE_NONE, 'Enable regex syntax for filter')
            ->addOption(self::OPTION_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Limit the result set', 20)
            ->addOption(self::OPTION_OFFSET, 'o', InputOption::VALUE_REQUIRED, 'Offset for result set', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filter = $input->getArgument(self::ARGUMENT_FILTER);
        $regex = $input->getOption(static::OPTION_REGEX);

        $output->write(sprintf('<action>Projection names'));
        if ($filter) {
            $output->write(sprintf(' filter <highlight>%s</highlight>', $filter));
        }
        if ($regex) {
            $output->write(' <comment>regex enabled</comment>');
            $method = 'fetchProjectionNamesRegex';
        } else {
            $method = 'fetchProjectionNames';
        }
        $output->writeln('</action>');

        $output->writeln(
            json_encode($this->projectionManager->$method(
                $filter,
                $input->getOption(self::OPTION_LIMIT),
                $input->getOption(self::OPTION_OFFSET)
            ), JSON_PRETTY_PRINT)
        );
    }
}
