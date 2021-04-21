# Migrating from 0.8.0

Due to [the future of prooph components](https://www.sasaprolic.com/2018/08/the-future-of-prooph-components.html)
and [ES/Service-Bus discussion](https://github.com/prooph/event-sourcing/issues/90)
, `prooph/event-sourcing` dependency was dropped for this bundle after `0.8.0`. You can still use it, but additional
work must be done after upgrading this bundle to `0.9.0` or further.

## Explicit installation of event sourcing component

`prooph/event-sourcing` is still available and can be installed by single composer command

```console
$ composer install prooph/event-sourcing
```

## Explicit definition of aggregate repositories

Aggregate repositories are part of `prooph/event-sourcing` however, bundle will no longer register repositories as a
services. You have to configure those by yourself. Let's consider following configuration:

```yaml
prooph_event_store:
    stores:
        default:
            event_store: 'Prooph\EventStore\EventStore'
            repositories:
                some_aggregate:
                    repository_class: App\Infrastructure\Persistence\SomeAggregateRepository
                    aggregate_type: App\Domain\Model\SomeAggregate
                    aggregate_translator: Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator
                    stream_name: 'some_aggregate_stream'

    projection_managers:
        default_projection_manager:
            event_store: '@prooph_event_store.default'
            connection: '@app.event_store.pdo'
            projections:
                some_projection:
                    read_model: App\Infrastructure\Projection\SomeProjectionReadModel
                    projection: App\Infrastructure\Projection\SomeProjection

services:
    Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator: ~

    Prooph\EventStore\EventStore:
        class: 'Prooph\EventStore\Pdo\PostgresEventStore'
        arguments:
            - '@prooph_event_store.message_factory'
            - '@app.event_store.pdo'
            - '@app.event_store.persistence_strategy'

    app.event_store.pdo:
        class: \PDO

    app.event_store.postgres.persistence_strategy:
        class: Prooph\EventStore\Pdo\PersistenceStrategy\PostgresSimpleStreamStrategy
```

As you can see, there is a single EventStore configured with one repository. To make application work the same you have
to

1. define a service of class `Prooph\EventSourcing\Aggregate\AggregateType` and `Prooph\EventStore\StreamName` for each
   aggregate, so it can be injected into repository
2. define each repository as a service

However, `AggregateRepository` requires such dependencies as `Prooph\EventSourcing\Aggregate\AggregateType`
and/or `Prooph\EventStore\StreamName`. It can be a bit overhead defining those as services as well. Since your
repository class is defined as a service already, you can
overwrite `Prooph\EventSourcing\Aggregate\AggregateRepository::__construct` like this:

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\SomeAggregate;
use Prooph\EventSourcing\Aggregate\AggregateRepository;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\StreamName;
use Prooph\SnapshotStore\SnapshotStore;

class SomeAggregateRepository extends AggregateRepository
{
    public function __construct(EventStore $eventStore, SnapshotStore $snapshotStore)
    {
        parent::__construct(
            $eventStore,
            AggregateType::fromAggregateRootClass(SomeAggregate::class),
            new AggregateTranslator(),
            $snapshotStore,
            new StreamName('some_aggregate_stream')
        );
    }
}
```

This will let you use autowiring without any additional configuration. Your configuration should look as follows:

```yaml
prooph_event_store:
    stores:
        default:
            event_store: 'Prooph\EventStore\EventStore'

    projection_managers:
        default_projection_manager:
            event_store: '@prooph_event_store.default'
            connection: '@app.event_store.pdo'
            projections:
                some_projection:
                    read_model: App\Infrastructure\Projection\SomeProjectionReadModel
                    projection: App\Infrastructure\Projection\SomeProjection

services:

    Prooph\EventStore\EventStore:
       class: 'Prooph\EventStore\Pdo\PostgresEventStore'
       arguments:
          - '@prooph_event_store.message_factory'
          - '@app.event_store.pdo'
          - '@app.event_store.persistence_strategy'

    app.event_store.pdo:
       class: \PDO

    app.event_store.postgres.persistence_strategy:
        class: Prooph\EventStore\Pdo\PersistenceStrategy\PostgresSimpleStreamStrategy
```
