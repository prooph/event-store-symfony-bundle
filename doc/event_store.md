# Event Store

This documentation covers just the configuration of Prooph Event Stores in Symfony.
To inform yourself about the Prooph Event Store, please have a look at its
[official documentation](http://docs.getprooph.org/event-store/).

## Setting up a MySQL PDO Event Store

To setup a MySQL PDO Event Store we need the `prooph/pdo-event-store` package.

```bash
composer require prooph/pdo-event-store
```

> **Hint**: You can also follow this instruction if you want to setup a PDO Event Store for MariaDB or PostgreSQL.
> You just need to use other classes which are also part of the `prooph/pdo-event-store` package.
> For further details please have a look at the [prooph/pdo-event-store package](https://github.com/prooph/pdo-event-store).

Before we setup our event store, we need to setup some services:

```yaml
# app/config/services.yml or (flex) config/packages/prooph_event_store.yaml
services:
    prooph_event_store.pdo_mysql_event_store:
        class: Prooph\EventStore\Pdo\MySqlEventStore
        arguments:
            - '@prooph_event_store.message_factory'
            - '@pdo.connection'
            - '@prooph_event_store.mysql.single_stream_strategy'
            
    prooph_event_store.mysql.single_stream_strategy:
        class: Prooph\EventStore\Pdo\PersistenceStrategy\MySqlSingleStreamStrategy
        
    pdo.connection:
        class: PDO
        arguments: ['%dsn%']
```

> **Hint**: For reusing a PDO connection from Doctrine please see below.

> **Hint**: You can also use other stream strategies.
> Have a look at the documentation of the [prooph/pdo-event-store package](https://github.com/prooph/pdo-event-store/blob/master/docs/variants.md)
> to learn about the different strategies.
> See below for further information within this bundle.

## Reusing a PDO connection from Doctrine

If you already have a PDO connection configured through doctrine
and you want to use the same connection for your event store,
there is a simple way to reuse it:

```yaml
# app/config/services.yaml
services:
    prooph_event_store.connection.doctrine_pdo_connection:
        class: PDO
        factory: ['@doctrine.dbal.default_connection', getWrappedConnection]
```

## Using different Stream Strategies

To make yourself familiar with different stream strategies,
please have a look at the documentation of the [prooph/pdo-event-store package](https://github.com/prooph/pdo-event-store/blob/master/docs/variants.md).

If you want to use one of the Single Stream Strategies, you just need to set up the Strategy as a service and pass it to the event store:

```yaml
# app/config/services.yml or (flex) config/packages/prooph_event_store.yaml
services:
    prooph_event_store.pdo_mysql_event_store:
        class: Prooph\EventStore\Pdo\MySqlEventStore
        arguments:
            - '@prooph_event_store.message_factory'
            - '@pdo.connection'
            - '@prooph_event_store.mysql.single_stream_strategy'
            
    prooph_event_store.mysql.single_stream_strategy:
        class: Prooph\EventStore\Pdo\PersistenceStrategy\MySqlSingleStreamStrategy
        
    pdo.connection:
        class: PDO
        arguments: ['%dsn%']
```
