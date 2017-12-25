# Configuration Reference

```yaml
prooph_event_store:
    stores:
        acme_store:
            event_store: Prooph\EventStore\Pdo\MysqlEventStore
            repositories:
                todo_list:
                    repository_class: Prooph\ProophessorDo\Infrastructure\Repository\EventStoreUserCollection
                    aggregate_type: Prooph\ProophessorDo\Model\User\User
                    aggregate_translator: prooph_event_sourcing.aggregate_translator
    projection_managers:
        event_store: Prooph\EventStore\Pdo\MysqlEventStore
        connection: 'doctrine.pdo.connection'
        projections:
            user_projection:
                read_model: Prooph\ProophessorDo\Projection\User\UserReadModel
                projection: Prooph\ProophessorDo\Projection\User\UserProjection
```
