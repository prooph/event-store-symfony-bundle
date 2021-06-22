# Projection Manager

Projection Managers will help you to create persistent projections from your event stream.
For further information please have a look at the [official documentation](http://docs.getprooph.org/event-store/projections.html).

Before you can setup an Projection Manager, you need to setup at least one [Event Store](./event_store.html).

Then you can add a Projection Manager:

```yaml
# app/config/config.yml or (flex) config/packages/prooph_event_store.yaml
prooph_event_store:
    projection_managers:
        acme_projection_manager:
            event_store: 'prooph_event_store.pdo_mysql_event_store'
            connection: 'pdo.connection'
```

Currently, the bundle is limited to support Projection Managers for Event Stores
that are either part of the [prooph/pdo-event-store package](https://github.com/prooph/pdo-event-store)
or an `Prooph\EventStore\InMemoryEventStore`.
In the latter case you can omit the `connection`.

!!!Be aware, that Event Store is passed by name `prooph_event_store.pdo_mysql_event_store`. During compilation, bundle wraps
EventStore with a decorator and register it under `prooph_event_store.<NAME>` ID. 

Having configuration as follows:

```yaml
prooph_event_store:
    stores:
        default:
            event_store: 'prooph_event_store.pdo_mysql_event_store'
```

`prooph_event_store.default` should be used if you have to reference your Event Store, otherwise you will be referencing 
a service without additional plugins nor enrichers.

Because a projection manager is worthless without connections he can manage, let's configure some projections.

## Configure a projection

To configure a projection we need a Projection before.

Projections might either implement `Prooph\Bundle\EventStore\Projection`
or implement `Prooph\Bundle\EventStore\Projection\ReadModelProjection`.

Both interfaces have just one method to configure the projection as explained in the [Event Store documentation](http://docs.getprooph.org/event-store/projections.html).

To give one example from [proophessor-do-symfony](https://github.com/prooph/proophessor-do-symfony)
here is a Read Model:

```php
<?php
declare(strict_types=1);

namespace Prooph\ProophessorDo\Projection\Todo;

use Doctrine\DBAL\Connection;
use Prooph\EventStore\Projection\AbstractReadModel;

final class TodoReadModel extends AbstractReadModel
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function init(): void
    {
        $sql = <<<EOT
CREATE TABLE `read_todo` (
  `id` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  `assignee_id` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  `text` longtext COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `deadline` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reminder` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_a_status` (`assignee_id`,`status`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOT;
        $this->connection->executeQuery($sql);
    }

    public function isInitialized(): bool
    {
        $statement = $this->connection->executeQuery('SHOW TABLES LIKE read_todo;');
        return $statement->fetch() !== false;
    }

    public function reset(): void
    {
        $this->connection->executeQuery('TRUNCATE TABLE read_todo;');
    }

    public function delete(): void
    {
        $this->connection->executeQuery('DROP TABLE read_todo;');
    }

    protected function insert(array $data): void
    {
        $this->connection->insert('read_todo', $data);
    }

    protected function update(array $data, array $identifier): void
    {
        $this->connection->update('read_todo', $data, $identifier);
    }
}
```

and the projection that uses the ReadModel:

```php
<?php
declare(strict_types=1);

namespace Prooph\ProophessorDo\Projection\Todo;

use Prooph\Bundle\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ReadModelProjector;
use Prooph\ProophessorDo\Model\Todo\Event\DeadlineWasAddedToTodo;
use Prooph\ProophessorDo\Model\Todo\Event\ReminderWasAddedToTodo;
use Prooph\ProophessorDo\Model\Todo\Event\TodoWasMarkedAsDone;
use Prooph\ProophessorDo\Model\Todo\Event\TodoWasMarkedAsExpired;
use Prooph\ProophessorDo\Model\Todo\Event\TodoWasPosted;
use Prooph\ProophessorDo\Model\Todo\Event\TodoWasReopened;
use Prooph\ProophessorDo\Model\Todo\Event\TodoWasUnmarkedAsExpired;

final class TodoProjection implements ReadModelProjection
{
    public function project(ReadModelProjector $projector): ReadModelProjector
    {
        $projector->fromStream('event_stream')
            ->when([
                TodoWasPosted::class => function ($state, TodoWasPosted $event) {
                    /** @var TodoReadModel $readModel */
                    $readModel = $this->readModel();
                    $readModel->stack('insert', [
                        'id' => $event->todoId()->toString(),
                        'assignee_id' => $event->assigneeId()->toString(),
                        'text' => $event->text()->toString(),
                        'status' => $event->todoStatus()->toString(),
                    ]);
                },
                TodoWasMarkedAsDone::class => function ($state, TodoWasMarkedAsDone $event) {
                    /** @var TodoReadModel $readModel */
                    $readModel = $this->readModel();
                    $readModel->stack(
                        'update',
                        ['status' => $event->newStatus()->toString()],
                        ['id' => $event->todoId()->toString()]
                    );
                },
                TodoWasReopened::class => function ($state, TodoWasReopened $event) {
                    /** @var TodoReadModel $readModel */
                    $readModel = $this->readModel();
                    $readModel->stack(
                        'update',
                        ['status' => $event->status()->toString()],
                        ['id' => $event->todoId()->toString()]
                    );
                },
                DeadlineWasAddedToTodo::class => function ($state, DeadlineWasAddedToTodo $event) {
                    /** @var TodoReadModel $readModel */
                    $readModel = $this->readModel();
                    $readModel->stack(
                        'update',
                        ['deadline' => $event->deadline()->toString()],
                        ['id' => $event->todoId()->toString()]
                    );
                },
                ReminderWasAddedToTodo::class => function ($state, ReminderWasAddedToTodo $event) {
                    /** @var TodoReadModel $readModel */
                    $readModel = $this->readModel();
                    $readModel->stack(
                        'update',
                        ['reminder' => $event->reminder()->toString()],
                        ['id' => $event->todoId()->toString()]
                    );
                },
                TodoWasMarkedAsExpired::class => function ($state, TodoWasMarkedAsExpired $event) {
                    /** @var TodoReadModel $readModel */
                    $readModel = $this->readModel();
                    $readModel->stack(
                        'update',
                        ['status' => $event->newStatus()->toString()],
                        ['id' => $event->todoId()->toString()]
                    );
                },
                TodoWasUnmarkedAsExpired::class => function ($state, TodoWasUnmarkedAsExpired $event) {
                    /** @var TodoReadModel $readModel */
                    $readModel = $this->readModel();
                    $readModel->stack(
                        'update',
                        ['status' => $event->newStatus()->toString()],
                        ['id' => $event->todoId()->toString()]
                    );
                },
            ]);

        return $projector;
    }
}
```

A lot of code, but really simple one. Its configuration is shorter.
First we need to define a service definition for both:

```yaml
# app/config/services.yml
services:
    proophessor.projection.todo:
        class: Prooph\ProophessorDo\Projection\Todo\TodoProjection
        
    proophessor.projection.read_model.todo:
        class: Prooph\ProophessorDo\Projection\Todo\TodoReadModel
```

Now we have two possibilities to configure the projections.

## Tags

We can add a Tag to the projection:

```yaml
# app/config/services.yml
services:
    proophessor.projection.todo:
        class: Prooph\ProophessorDo\Projection\Todo\TodoProjection
        tags:
            - { name: prooph_event_store.projection, projection_name: todo_projection, projection_manager: acme_projection_manager, read_model: 'proophessor.projection.read_model.todo' }
        
    proophessor.projection.read_model.todo:
        class: Prooph\ProophessorDo\Projection\Todo\TodoReadModel
```

While the `projection_name` is freely selectable, the `projection_manager` must reference an existing Projection Manager
(like the one we configured above).
The `read_model` attribute is necessary only if the projection implements `Prooph\Bundle\EventStore\Projection\ReadModelProjection`. 

## Central

If you do not like tags or want to configure your projections at a central place,
you can do this directly at the `projection_manager`:

```yaml
# app/config/config.yml or (flex) config/packages/prooph_event_store.yaml
prooph_event_store:
    projection_managers:
        acme_projection_manager:
            event_store: 'prooph_event_store.pdo_mysql_event_store'
            connection: 'pdo.connection'
            projections:
                todo_projection:
                    read_model: 'proophessor.projection.read_model.todo' 
                    projection: 'proophessor.projection.todo'
```

As with the tag the `read_model` is necessary only if the projection implements `Prooph\Bundle\EventStore\Projection\ReadModelProjection`. 

Since both ways will produce the same result, it is up to you which of them you choose. 

## Projection options

If you run projection you would want to pass options to it. This can be done in two ways.

### Central

Scalar options can be added to projection defined directly at the `projection_manager`:

```yaml
# app/config/config.yml or (flex) config/packages/prooph_event_store.yaml
prooph_event_store:
    projection_managers:
        acme_projection_manager:
            event_store: 'prooph_event_store.pdo_mysql_event_store'
            connection: 'pdo.connection'
            projections:
                todo_projection:
                    read_model: 'proophessor.projection.read_model.todo' 
                    projection: 'proophessor.projection.todo'
                    options:
                        cache_size: 1000
                        sleep: 100000
                        persist_block_size: 1000
                        lock_timeout_ms: 1000
                        trigger_pcntl_dispatch: false
                        update_lock_threshold: 0
                        gap_detection:
                            retry_config: [0, 5, 10, 15, 30, 60, 90]
                            detection_window: 'P1M'
```

### Tagged service

If you want more complex usage, you can define tagged service which implements `\Prooph\Bundle\EventStore\Projection\ProjectionOptions`. 
It should be tagged as `prooph_event_store.projection_options` with `projection_name` attribute pointing to specific projection.

```php
<?php

declare(strict_types=1);

namespace Prooph\ProophessorDo\Projection\Options;

use Prooph\Bundle\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Pdo\Projection\GapDetection;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class ToDoProjectionOptions implements ProjectionOptions
{
    private ParameterBag $parameterBag;

    public function __construct(ParameterBag $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }
    
    public function options(): array
    {
        return [
            'cache_size' => $this->parameterBag->get('projection.cache_size'),
            'sleep' => $this->parameterBag->get('projection.sleep'),
            'persist_block_size' => $this->parameterBag->get('projection.persist_block_size'),
            'lock_timeout_ms' => $this->parameterBag->get('projection.lock_timeout_ms'),
            'trigger_pcntl_dispatch' => $this->parameterBag->get('projection.trigger_pcntl_dispatch'),
            'update_lock_threshold' => $this->parameterBag->get('projection.update_lock_threshold'),
            'gap_detection' => new GapDetection([0, 5, 5, 10, 15, 25, 40, 65, 105]),
        ];
    }
}
```

```yaml
services:
    Prooph\ProophessorDo\Projection\Options\ToDoProjectionOptions:
        tags:
            - { name: prooph_event_store.projection_options, projection_name: todo_projection }
```

## Manage projections

Running a projection
```
$ bin/console event-store:projection:run [options] [--] <projection-name>

Arguments:
  projection-name       The name of the Projection

Options:
    -o, --run-once        Loop the projection only once, then exit
```

Stopping a projection
```
$ bin/console event-store:projection:stop <projection-name>

Arguments:
  projection-name       The name of the Projection
```

Resetting a projection
```
$ bin/console event-store:projection:reset <projection-name>

Arguments:
  projection-name       The name of the Projection
```

Showing the current projection state
```
$ bin/console event-store:projection:state <projection-name>

Arguments:
  projection-name       The name of the Projection
```

Deleting a projection
```
$ bin/console event-store:projection:delete [options] [--] <projection-name>

Arguments:
  projection-name            The name of the Projection

Options:
  -w, --with-emitted-events  Delete with emitted events
```

Showing a list of all projection names. Can be filtered.
```
$ bin/console event-store:projection:names [options] [--] [<filter>]

Arguments:
  filter                 Filter by this string

Options:
  -r, --regex            Enable regex syntax for filter
  -l, --limit=LIMIT      Limit the result set [default: 20]
  -o, --offset=OFFSET    Offset for result set [default: 0]
  -m, --manager=MANAGER  Manager for result set
```
