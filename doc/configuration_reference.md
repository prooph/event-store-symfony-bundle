# Configuration Reference

```yaml
prooph_event_store:
    stores:
        acme_store:
            event_emitter: Prooph\Common\Event\ProophActionEventEmitter
            wrap_action_event_emitter: true
            event_store: Prooph\EventStore\Pdo\MysqlEventStore
    projection_managers:
        main_manager:
            event_store: 'prooph_event_store.acme_store'
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

## projection_managers

### event_store

This should be reference of an EventStore which will be injected into ProjectionManager. Be aware, that this shouldn't be 
ID of a service that implements `Prooph\EventStore\EventStore`, but service configured in `stores` section, eg. `prooph_event_store.acme_store`. 
This will inject proper service which can be configured with additional functions like plugins or enrichers.

### connection

If you are using PDO-based `EventStore`, manager require you to inject the `PDO` instance. 
Please have a look at [the projection manager section](./projection_manager.md) of this documentation for further details. 

### event_streams_table

### projection_table

Self-explanatory. Table name in which Projection Manager will hold current information about managed projections.

### projections

Collection of projections managed by Projection Manager.

#### read_model

ID of a service that implements `Prooph\EventStore\Projection\ReadModel` interface for persistent projections. 
ReadModel is used to update data in (you guessed) read-only data storage.

#### projection

ID of a service that implements `Prooph\Bundle\EventStore\Projection\Projection` or `Prooph\Bundle\EventStore\Projection\ReadModelProjection` 
for persistent projections. Implementation should configure how events of a certain Aggregate will be handled while running projection.
