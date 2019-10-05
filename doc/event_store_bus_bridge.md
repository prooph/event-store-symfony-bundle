# Event Store Bus Bridge

While both the Prooph Event Store Symfony Bundle and the [Prooph Service Bus Symfony Bundle](https://github.com/prooph/service-bus-symfony-bundle/)
are useful on its own, usually you want to use both together.

To combine them, you will need the [Prooph Event Store Bus Bridge](https://github.com/prooph/event-store-bus-bridge)
that can be required with composer:

```bash
composer require prooph/event-store-bus-bridge
``` 

There are three locations where we can combine both bundles.

## Transaction Manager

The transaction manager starts a new event store transaction on every command dispatch and commits its afterwards.
To enable it for an Event Store, you need to add a service and tag it as a plugin for a service bus.

Assuming that we have an Event Store named `acme_store` and a Service Bus named `acme_command_bus`
the configuration might look like this:

```yaml
# app/config/services.yml
services:
    prooph_event_store_bus_bridge.acme_transaction_manager:
        class: Prooph\EventStoreBusBridge\TransactionManager
        arguments: ['@prooph_event_store.acme_store']
        tags:
          - { name: 'prooph_service_bus.acme_command_bus.plugin' }
```

## Event Publisher

The Event Publisher is an Event Store Plugin which listens on the Event Store and publishes recorded message on the Event Bus.

Assuming that we have an Event Store named `acme_store` and an Event Bus named `acme_event_bus`
the configuration might look like this:

```yaml
# app/config/services.yml
services:
    prooph_event_store_bus_bridge.acme_event_publisher:
        class: Prooph\EventStoreBusBridge\EventPublisher
        arguments: ['@prooph_service_bus.acme_event_bus']
        tags:
          - { name: 'prooph_event_store.acme_store.plugin' }
```

## Causation Metadata Enricher

The Causation Metadata Enricher will add causation metadata to each recorded event.

To enable it for all command buses and all event stores you can use a configuration like this: 

```yaml
# app/config/services.yml
services:
    prooph_event_store_bus_bridge.causation_metadata_enricher:
        class: Prooph\EventStoreBusBridge\CausationMetadataEnricher
        tags:
          - { name: 'prooph_service_bus.command_bus.plugin' }
          - { name: 'prooph_event_store.plugin' }
```
