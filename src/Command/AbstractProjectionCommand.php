<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Bundle\EventStore\Projection\Projection;
use Prooph\Bundle\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractProjectionCommand extends Command
{
    use FormatsOutput;

    protected const ARGUMENT_PROJECTION_NAME = 'projection-name';

    /**
     * @var ProjectionManager
     */
    protected $projectionManager;

    /**
     * @var string
     */
    protected $projectionName;

    /**
     * @var ReadModel|null
     */
    protected $readModel;

    /**
     * @var Projector|ReadModelProjector
     */
    protected $projector;

    /**
     * @var Projection|ReadModelProjection
     */
    protected $projection;

    /**
     * @var ContainerInterface
     */
    private $projectionManagerForProjectionsLocator;

    /**
     * @var ContainerInterface
     */
    protected $projectionsLocator;

    /**
     * @var ContainerInterface
     */
    protected $projectionReadModelLocator;

    public function __construct(
        ContainerInterface $projectionManagerForProjectionsLocator,
        ContainerInterface $projectionsLocator,
        ContainerInterface $projectionReadModelLocator
    ) {
        $this->projectionManagerForProjectionsLocator = $projectionManagerForProjectionsLocator;
        $this->projectionsLocator = $projectionsLocator;
        $this->projectionReadModelLocator = $projectionReadModelLocator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument(static::ARGUMENT_PROJECTION_NAME, InputArgument::REQUIRED, 'The name of the Projection');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $input->validate();

        $this->formatOutput($output);

        $this->projectionName = $input->getArgument(static::ARGUMENT_PROJECTION_NAME);

        if (! $this->projectionManagerForProjectionsLocator->has($this->projectionName)) {
            throw new RuntimeException(\sprintf('ProjectionManager for "%s" not found', $this->projectionName));
        }
        $this->projectionManager = $this->projectionManagerForProjectionsLocator->get($this->projectionName);

        if (! $this->projectionsLocator->has($this->projectionName)) {
            throw new RuntimeException(\sprintf('Projection "%s" not found', $this->projectionName));
        }
        $this->projection = $this->projectionsLocator->get($this->projectionName);

        if ($this->projection instanceof ReadModelProjection) {
            if (! $this->projectionReadModelLocator->has($this->projectionName)) {
                throw new RuntimeException(\sprintf('ReadModel for "%s" not found', $this->projectionName));
            }
            $this->readModel = $this->projectionReadModelLocator->get($this->projectionName);

            $this->projector = $this->projectionManager->createReadModelProjection($this->projectionName, $this->readModel);
        }

        if ($this->projection instanceof Projection) {
            $this->projector = $this->projectionManager->createProjection($this->projectionName);
        }

        if (null === $this->projector) {
            throw new RuntimeException('Projection was not created');
        }
        $output->writeln(\sprintf('<header>Initialized projection "%s"</header>', $this->projectionName));
        try {
            $state = $this->projectionManager->fetchProjectionStatus($this->projectionName)->getValue();
        } catch (\Prooph\EventStore\Exception\RuntimeException $e) {
            $state = 'unknown';
        }
        $output->writeln(\sprintf('<action>Current status: <highlight>%s</highlight></action>', $state));
        $output->writeln('====================');
    }
}
