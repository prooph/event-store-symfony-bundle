# Configuration Reference

```yaml
prooph_event_store:
    stores:
        acme_store:
            event_emitter: Prooph\Common\Event\ProophActionEventEmitter
            wrap_action_event_emitter: true
            event_store: Prooph\EventStore\Pdo\MysqlEventStore
            repositories:
                todo_list:
                    repository_class: Prooph\ProophessorDo\Infrastructure\Repository\EventStoreUserCollection
                    aggregate_type: Prooph\ProophessorDo\Model\User\User
                    aggregate_translator: prooph_event_sourcing.aggregate_translator
                    snapshot_store: ~
                    stream_name: ~
                    one_stream_per_aggregate: false
    projection_managers:
        main_manager:
            event_store: Prooph\EventStore\Pdo\MysqlEventStore
            connection: 'doctrine.pdo.connection'
            event_streams_table: 'event_streams'
            projection_table: 'projections'
            projections:
                user_projection:
                    read_model: Prooph\ProophessorDo\Projection\User\UserReadModel
                    projection: Prooph\ProophessorDo\Projection\User\UserProjection
```

## stores

*Optional*

This section contains the configuration of your event stores.
Please have a look at [the event store section](./event_store.md) of this documentation for further details.
The name of the event store will be part of its service id: `prooph_event_store.<name>`.
For the `acme_store` in our example above it will be `prooph_event_store.acme_store`. 

### event_emitter

*Optional*

The event emitter that is used by the ActionEventEmitterEventStore.
It must be a class that implements `Prooph\Common\Event\ActionEventEmitter`.
The default value should be fine for most use cases.


### wrap_action_event_emitter

*Optional*

Should the given event store be decorated by an ActionEventEmitterEventStore?
In most cases you should keep this with the default value `true`.


### event_store

*Required*

The id of a service whose class implements `Prooph\EventStore\EventStore`.
Please have a look at [the event store section](./event_store.md) of this documentation for further details.

#### stream_name

*Optional*

You can pass a string as custom stream name if you want.

#### one_stream_per_aggregate

*Optional*

Should the repository create an own single stream for each aggregate?  
See section *Using different Stream Strategies* for of [the event store section](./event_store.md) of this documentation for further details.

## projection_managers

### event_store

### connection

### event_streams_table

### projection_table

### projections

#### read_model

#### projection
