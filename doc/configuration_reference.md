# Configuration Reference

```yaml
prooph_event_store:
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

## projection_managers

### event_store

### connection

### event_streams_table

### projection_table

### projections

#### read_model

#### projection
