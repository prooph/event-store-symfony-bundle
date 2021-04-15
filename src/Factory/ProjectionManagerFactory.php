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

namespace Prooph\Bundle\EventStore\Factory;

use PDO;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Pdo\MariaDbEventStore;
use Prooph\EventStore\Pdo\MySqlEventStore;
use Prooph\EventStore\Pdo\PostgresEventStore;
use Prooph\EventStore\Pdo\Projection\MariaDbProjectionManager;
use Prooph\EventStore\Pdo\Projection\MySqlProjectionManager;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Prooph\EventStore\Projection\ProjectionManager;

class ProjectionManagerFactory
{
    public function createProjectionManager(
        EventStore $eventStore,
        ?PDO $connection = null,
        string $eventStreamsTable = 'event_streams',
        string $projectionsTable = 'projections'
    ): ProjectionManager {
        $checkConnection = static function () use ($connection): PDO {
            if (! $connection instanceof PDO) {
                throw new RuntimeException('PDO connection missing');
            }

            return $connection;
        };

        $realEventStore = $this->getTheRealEventStore($eventStore);

        if ($realEventStore instanceof InMemoryEventStore) {
            return new InMemoryProjectionManager($eventStore);
        }

        if ($realEventStore instanceof PostgresEventStore) {
            return new PostgresProjectionManager($eventStore, $checkConnection(), $eventStreamsTable, $projectionsTable);
        }

        if ($realEventStore instanceof MySqlEventStore) {
            return new MySqlProjectionManager($eventStore, $checkConnection(), $eventStreamsTable, $projectionsTable);
        }

        if ($realEventStore instanceof MariaDbEventStore) {
            return new MariaDbProjectionManager($eventStore, $checkConnection(), $eventStreamsTable, $projectionsTable);
        }

        throw new RuntimeException(\sprintf('ProjectionManager for %s not implemented.', \get_class($realEventStore)));
    }

    /**
     * Gets the "real" event store in case we were provided with an EventStoreDecorator.
     * That's the one that will really perfom the actions.
     *
     * @param EventStore $eventStore
     *
     * @return EventStore
     */
    private function getTheRealEventStore(EventStore $eventStore): EventStore
    {
        $realEventStore = $eventStore;

        while ($realEventStore instanceof EventStoreDecorator) {
            $realEventStore = $realEventStore->getInnerEventStore();
        }

        return $realEventStore;
    }
}
