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
So for the `acme_store` in our example above it will be `prooph_event_store.acme_store`. 

### event_emitter

*Optional*


### wrap_action_event_emitter

*Optional*


### event_store

*Required*



### repositories

*Optional*


#### repository_class

*Optional*


#### aggregate_type

*Optional*


#### snapshot_store

*Optional*


#### stream_name

*Optional*


#### one_stream_per_aggregate

*Optional*


## projection_managers

### event_store

### connection

### event_streams_table

### projection_table

### projections

#### read_model

#### projection
