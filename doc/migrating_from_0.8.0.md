# Migrating from 0.8.0

Due to [the future of prooph components](https://www.sasaprolic.com/2018/08/the-future-of-prooph-components.html) 
and [ES/Service-Bus discussion](https://github.com/prooph/event-sourcing/issues/90) 
, `prooph/event-sourcing` dependency was dropped for this bundle after `0.8.0`. You can still use it, but additional work 
must be done after upgrading this bundle to `0.9.0` or further.

## Explicit installation of event sourcing component

`prooph/event-sourcing` is still available and can be installed by single composer command

```console
$ composer install prooph/event-sourcing
```

## Explicit definition of aggregate repositories

Aggregate repositories were part of `prooph/event-sourcing` and without it, bundle will no longer register repositories
as a services. You have to configure those by yourself. Let's consider following configuration

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

    app.event_store.mysql.persistence_strategy:
        class: Prooph\EventStore\Pdo\PersistenceStrategy\PostgresSimpleStreamStrategy
```

As you can see, there is a single EventStore configured with one repository. To make application work the same you have
to

1. define a service of class `Prooph\EventSourcing\Aggregate\AggregateType` and `Prooph\EventStore\StreamName` for each
   aggregate, so it can be injected into repository
2. define each repository as a service

Your configuration should be transformed as follows

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
    Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator: ~

    Prooph\EventStore\EventStore:
        class: 'Prooph\EventStore\Pdo\PostgresEventStore'
        arguments:
            - '@prooph_event_store.message_factory'
            - '@app.event_store.pdo'
            - '@app.event_store.persistence_strategy'

    app.some_aggregate.type:
       class: 'Prooph\EventSourcing\Aggregate\AggregateType'
       factory: [ 'Prooph\EventSourcing\Aggregate\AggregateType', 'fromAggregateRootClass' ]
       arguments:
          - 'App\Domain\Model\SomeAggregate'

    app.some_aggregate.stream:
       class: 'Prooph\EventStore\StreamName'
       arguments:
          - 'some_aggregate_stream'

    App\Infrastructure\Persistence\SomeAggregateRepository:
        arguments:
            $eventStore: '@prooph_event_store.default'
            $aggregateType: '@app.some_aggregate.type'
            $aggregateTranslator: Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator
            $streamName: '@app.some_aggregate.stream'
            $oneStreamPerAggregate: true

    app.event_store.pdo:
        class: \PDO

    app.event_store.mysql.persistence_strategy:
        class: Prooph\EventStore\Pdo\PersistenceStrategy\PostgresSimpleStreamStrategy
```

A bit overloading, but writing Event Sourcing component by yourself is quite easy (it takes few classes to work).
