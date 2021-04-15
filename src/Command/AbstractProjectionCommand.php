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

    protected ProjectionManager $projectionManager;

    protected string $projectionName;

    protected ?ReadModel $readModel;

    /**
     * @var Projector|ReadModelProjector
     */
    protected $projector;

    /**
     * @var Projection|ReadModelProjection
     */
    protected $projection;

    private ContainerInterface $projectionManagerForProjectionsLocator;
    private ContainerInterface $projectionsLocator;
    private ContainerInterface $projectionReadModelLocator;
    private ContainerInterface $projectionOptionsLocator;

    public function __construct(
        ContainerInterface $projectionManagerForProjectionsLocator,
        ContainerInterface $projectionsLocator,
        ContainerInterface $projectionReadModelLocator,
        ContainerInterface $projectionOptionsLocator
    ) {
        $this->projectionManagerForProjectionsLocator = $projectionManagerForProjectionsLocator;
        $this->projectionsLocator = $projectionsLocator;
        $this->projectionReadModelLocator = $projectionReadModelLocator;
        $this->projectionOptionsLocator = $projectionOptionsLocator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(static::ARGUMENT_PROJECTION_NAME, InputArgument::REQUIRED, 'The name of the Projection');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $input->validate();

        $this->formatOutput($output);

        $this->projectionName = $input->getArgument(static::ARGUMENT_PROJECTION_NAME);

        if (! $this->projectionManagerForProjectionsLocator->has($this->projectionName)) {
            throw new RuntimeException(\vsprintf('ProjectionManager for "%s" not found', \is_array($this->projectionName) ? $this->projectionName : [$this->projectionName]));
        }
        $this->projectionManager = $this->projectionManagerForProjectionsLocator->get($this->projectionName);

        if (! $this->projectionsLocator->has($this->projectionName)) {
            throw new RuntimeException(\vsprintf('Projection "%s" not found', \is_array($this->projectionName) ? $this->projectionName : [$this->projectionName]));
        }
        $this->projection = $this->projectionsLocator->get($this->projectionName);
        $projectionOptions = $this->projectionOptionsLocator->has($this->projectionName) ? $this->projectionOptionsLocator->get($this->projectionName)->options() : [];

        if ($this->projection instanceof ReadModelProjection) {
            if (! $this->projectionReadModelLocator->has($this->projectionName)) {
                throw new RuntimeException(\vsprintf('ReadModel for "%s" not found', \is_array($this->projectionName) ? $this->projectionName : [$this->projectionName]));
            }
            $this->readModel = $this->projectionReadModelLocator->get($this->projectionName);

            $this->projector = $this->projectionManager->createReadModelProjection($this->projectionName, $this->readModel, $projectionOptions);
        }

        if ($this->projection instanceof Projection) {
            $this->projector = $this->projectionManager->createProjection($this->projectionName, $projectionOptions);
        }

        if (null === $this->projector) {
            throw new RuntimeException('Projection was not created');
        }
        $output->writeln(\vsprintf('<header>Initialized projection "%s"</header>', \is_array($this->projectionName) ? $this->projectionName : [$this->projectionName]));
        try {
            $state = $this->projectionManager->fetchProjectionStatus($this->projectionName)->getValue();
        } catch (\Prooph\EventStore\Exception\RuntimeException $e) {
            $state = 'unknown';
        }
        $output->writeln(\sprintf('<action>Current status: <highlight>%s</highlight></action>', $state));
        $output->writeln('====================');
    }
}
