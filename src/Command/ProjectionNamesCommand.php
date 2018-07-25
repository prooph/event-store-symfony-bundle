<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectionNamesCommand extends Command
{
    use FormatsOutput;

    private const ARGUMENT_FILTER = 'filter';
    private const OPTION_REGEX = 'regex';
    private const OPTION_LIMIT = 'limit';
    private const OPTION_OFFSET = 'offset';
    private const OPTION_MANAGER = 'manager';

    /**
     * @var ContainerInterface
     */
    private $projectionManagersLocator;

    /**
     * @var array
     */
    private $projectionManagerNames;

    public function __construct(ContainerInterface $projectionManagersLocator, array $projectionManagerNames)
    {
        $this->projectionManagersLocator = $projectionManagersLocator;
        $this->projectionManagerNames = $projectionManagerNames;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('event-store:projection:names')
            ->setDescription('Shows a list of all projection names. Can be filtered.')
            ->addArgument(self::ARGUMENT_FILTER, InputArgument::OPTIONAL, 'Filter by this string')
            ->addOption(self::OPTION_REGEX, 'r', InputOption::VALUE_NONE, 'Enable regex syntax for filter')
            ->addOption(self::OPTION_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Limit the result set', 20)
            ->addOption(self::OPTION_OFFSET, 'o', InputOption::VALUE_REQUIRED, 'Offset for result set', 0)
            ->addOption(self::OPTION_MANAGER, 'm', InputOption::VALUE_REQUIRED, 'Manager for result set', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->formatOutput($output);

        $managerNames = \array_keys($this->projectionManagerNames);

        if ($requestedManager = $input->getOption(self::OPTION_MANAGER)) {
            $managerNames = \array_filter($managerNames, function (string $managerName) use ($requestedManager) {
                return $managerName === $requestedManager;
            });
        }

        $filter = $input->getArgument(self::ARGUMENT_FILTER);
        $regex = $input->getOption(static::OPTION_REGEX);

        $output->write(\sprintf('<action>Projection names'));
        if ($filter) {
            $output->write(\sprintf(' filter <highlight>%s</highlight>', $filter));
        }
        if ($regex) {
            $output->write(' <comment>regex enabled</comment>');
            $method = 'fetchProjectionNamesRegex';
        } else {
            $method = 'fetchProjectionNames';
        }
        $output->writeln('</action>');

        $names = [];
        $offset = (int) $input->getOption(self::OPTION_OFFSET);
        $limit = (int) $input->getOption(self::OPTION_LIMIT);
        $maxNeeded = $offset + $limit;

        foreach ($managerNames as $managerName) {
            $projectionManager = $this->projectionManagersLocator->get($managerName);

            if (\count($names) > $offset) {
                $projectionNames = $projectionManager->$method($filter, $limit - (\count($names) - $offset));
            } else {
                $projectionNames = $projectionManager->$method($filter, $limit);
            }

            foreach ($projectionNames as $projectionName) {
                $names[] = [$managerName, $projectionName];
            }

            if (\count($names) >= $maxNeeded) {
                break;
            }
        }

        $names = \array_slice($names, $offset, $limit);

        $table = new Table($output);
        $table
            ->setHeaders(['Projection Manager', 'Name'])
            ->setRows($names);

        $table->render();
    }
}
