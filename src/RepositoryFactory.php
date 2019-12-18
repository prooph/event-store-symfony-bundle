<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore;

use Prooph\EventSourcing\Aggregate\AggregateTranslator;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\StreamName;
use Prooph\SnapshotStore\SnapshotStore;

class RepositoryFactory
{
    /**
     * @return mixed
     */
    public function create(
        string $repositoryClass,
        EventStore $eventStore,
        string $aggregateType,
        AggregateTranslator $aggregateTranslator,
        SnapshotStore $snapshotStore = null,
        string $streamName = null,
        bool $oneStreamPerAggregate = false,
        bool $disableIdentityMap = false
    ) {
        return new $repositoryClass(
            $eventStore,
            AggregateType::fromAggregateRootClass($aggregateType),
            $aggregateTranslator,
            $snapshotStore,
            $streamName ? new StreamName($streamName) : null,
            $oneStreamPerAggregate,
            $disableIdentityMap
        );
    }
}
