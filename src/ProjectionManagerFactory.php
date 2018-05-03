<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore;

use PDO;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\EventStore\EventStore;
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
        $checkConnection = function () use ($connection): PDO {
            if (! $connection instanceof PDO) {
                throw new RuntimeException('PDO connection missing');
            }

            return $connection;
        };

        if ($eventStore instanceof InMemoryEventStore) {
            return new InMemoryProjectionManager($eventStore);
        }

        if ($eventStore instanceof PostgresEventStore) {
            return new PostgresProjectionManager($eventStore, $checkConnection(), $eventStreamsTable, $projectionsTable);
        }

        if ($eventStore instanceof MySqlEventStore) {
            return new MySqlProjectionManager($eventStore, $checkConnection(), $eventStreamsTable, $projectionsTable);
        }

        if ($eventStore instanceof MariaDbEventStore) {
            return new MariaDbProjectionManager($eventStore, $checkConnection(), $eventStreamsTable, $projectionsTable);
        }

        throw new RuntimeException(sprintf('ProjectionManager for %s not implemented.', get_class($eventStore)));
    }
}
