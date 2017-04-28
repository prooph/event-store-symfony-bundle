<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection;

use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractProjectionRunner extends Command
{
    const ARGUMENT_ACTION = 'action';
    const OPTION_RUN_ONCE = 'run-once';
    const OPTION_FILTER = 'filter';
    const OPTION_FILTER_REGEX = 'filter-regex';

    /**
     * @var ProjectionManager
     */
    private $projectionManager;
    /**
     * @var string
     */
    private $projectionName;
    /**
     * @var ReadModel
     */
    private $readModel;
    /**
     * @var ReadModelProjector|Projector
     */
    private $projector;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $reflectionClass = new ReflectionClass(static::class);
        if (
            !$reflectionClass->implementsInterface(ReadModelProjectionRunner::class)
            && !$reflectionClass->implementsInterface(ProjectionRunner::class)
        ) {
            throw new RuntimeException(
                sprintf(
                    'The class "%s" must implement either "%s" or "%s"',
                    static::class,
                    ReadModelProjectionRunner::class,
                    ProjectionRunner::class
                )
            );
        }
    }

    public function setProjectionManager(ProjectionManager $projectionManager): void
    {
        $this->projectionManager = $projectionManager;
    }

    public function setProjectionName(string $name): void
    {
        $this->projectionName = $name;
    }

    public function setReadModel(ReadModel $readModel): void
    {
        $this->readModel = $readModel;
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->addOption(static::OPTION_RUN_ONCE, 'o', InputOption::VALUE_NONE, 'Execute once')
            ->addOption(static::OPTION_FILTER, 'f', InputArgument::OPTIONAL, 'Filter by names on "projection-names"')
            ->addOption(static::OPTION_FILTER_REGEX, 'r', InputArgument::OPTIONAL,'RegEx filter by names on "projection-names". Higher priority than "filter" option')
            ->addArgument(static::ARGUMENT_ACTION, InputArgument::REQUIRED, <<<TXT
<info>The projection actions available:</info>
<comment>run</comment>
Run the projection.
<comment>reset</comment>
Reset a projection
<comment>delete</comment>
Delete a projection
<comment>delete-with-events</comment>
    Delete a projection with events
<comment>projection-state</comment>
    Show state
<comment>projection-names</comment> 
    Show projection names. You can filter by name with -f (--filter) or use a regex filter -r (--filter-regex)
<comment>projection-stream-position</comment>
    Show current stream positions
<info>
    Note: If you issue a:
        reset
        delete
        delete-with-events 
    action, you need to call the run action afterwards!
</info>
TXT
);


    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = $this->colors($output);
        if (null === $this->projectionManager) {
            throw new RuntimeException('No ProjectionManager provided');
        }
        if (null === $this->projectionName) {
            throw new RuntimeException('No projection provided');
        }

        if ($this instanceof ReadModelProjectionRunner) {
            if (null === $this->readModel()) {
                throw new RuntimeException('No ReadModel provided');
            }
            $this->projector = $this->projectionManager->createReadModelProjection($this->projectionName, $this->readModel());
        }

        if ($this instanceof ProjectionRunner) {
            $this->projector = $this->projectionManager->createProjection($this->projectionName);
        }

        $action = $input->getArgument(static::ARGUMENT_ACTION);
        $action = $this->actionName($action);
        $handler = sprintf('%sCommand', $action);

        if (!is_callable([$this, $handler])) {
            throw new InvalidArgumentException(sprintf(
                'Action "%s" not valid or not implemented',
                $action
            ));
        }

        $output->writeln(sprintf('<header>Executing "%s" on projection "%s</header>"', $action, $this->projectionName));
        $output->writeln('----------');
        $this->{$handler}($input, $output);
        $output->writeln('');
        $output->writeln(sprintf('<info>Execution "%s" finished</info>"', $action));
    }

    private function actionName($action)
    {
        $actionMap = [
            'delete-with-events' => 'deleteWithEvents',
            'projection-state' => 'projectionState',
            'projection-names' => 'projectionNames',
            'projection-stream-position' => 'projectionStreamPositions',
        ];
        if (!isset($actionMap[$action])) {
            return $action;
        }

        return $actionMap[$action];
    }

    private function runCommand(InputInterface $input, OutputInterface $output)
    {
        $keepRunning = !$input->getOption(static::OPTION_RUN_ONCE);
        $output->writeln(
            sprintf(
                '<action>Starting projection <highlight>%s</highlight>. Keep running: <highlight>%s</highlight></action>', $this->projectionName,
                $keepRunning === true ? 'enabled' : 'disabled'
            )
        );
        $projector = $this->project($this->projector);
        $projector->run((bool)$keepRunning);
        $output->writeln(sprintf('<action>Stopped projection <highlight>%s</highlight></action>', $this->projectionName));
    }

    private function stopCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<action>Stopping projection <highlight>%s</highlight></action>', $this->projectionName));
        $this->projectionManager->stopProjection($this->projectionName);
    }

    private function resetCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<action>Resetting projection <highlight>%s</highlight></action>', $this->projectionName));
        $this->projectionManager->resetProjection($this->projectionName);
    }

    private function deleteCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<action>Deleting projection </action><highlight>%s</highlight>', $this->projectionName));
        $this->projectionManager->deleteProjection($this->projectionName, false);
    }

    private function deleteWithEventsCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<action>Deleting projection <highlight>%s</highlight> with emitted events</action>', $this->projectionName));
        $this->projectionManager->deleteProjection($this->projectionName, true);
    }


    private function projectionStateCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<action>All states for this projection manager:</action>', $this->projectionName));
        $output->writeln(json_encode($this->projectionManager->fetchProjectionState($this->projectionName), JSON_PRETTY_PRINT));
    }

    private function projectionNamesCommand(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getOption(static::OPTION_FILTER_REGEX)) {
            $output->writeln(sprintf('<action>Projection names with regex filter <highlight>%s</highlight> on this projection manager:</action>',
                $input->getOption(static::OPTION_FILTER_REGEX)));
            $output->writeln(json_encode($this->projectionManager->fetchProjectionNamesRegex($input->getOption(static::OPTION_FILTER_REGEX)),
                JSON_PRETTY_PRINT));

            return;
        }

        if ($input->getOption(static::OPTION_FILTER)) {
            $output->writeln(sprintf('<action>Projection names with filter <highlight>"%s"</highlight> on this projection manager:</action>',
                $input->getOption(static::OPTION_FILTER)));
            $output->writeln(json_encode($this->projectionManager->fetchProjectionNames($input->getOption(static::OPTION_FILTER)), JSON_PRETTY_PRINT));

            return;
        }

        $output->writeln(sprintf('<action>All projection names on this projection manager:</action>', $this->projectionName));
        $output->writeln(json_encode($this->projectionManager->fetchProjectionNames($this->projectionName), JSON_PRETTY_PRINT));
    }

    private function projectionStreamPositionsCommand(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln(sprintf('<action>All stream positions on this projection manager:</action>', $this->projectionName));
        $table = (new Table($output))->setHeaders(['Stream', 'Position']);
        foreach ($this->projectionManager->fetchProjectionStreamPositions($this->projectionName) as $stream => $position) {
            $table->addRow([$stream, $position]);
        }
        $table->render();
    }

    private function colors(OutputInterface $output): OutputInterface
    {

        $outputFormatter = $output->getFormatter();
        $outputFormatter->setStyle('header', new OutputFormatterStyle('green', null));
        $outputFormatter->setStyle('highlight', new OutputFormatterStyle('green', null, ['bold']));
        $outputFormatter->setStyle('action', new OutputFormatterStyle('blue', null));


        return $output;
    }
}
