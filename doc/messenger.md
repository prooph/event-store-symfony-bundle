# Messenger integration

This bundle provides a middleware for the
`symfony/messenger` component (from version `4.3`) which handles
starting/committing/rolling back a transaction when sending a command
to the bus.

Here is an example configuration on how to use it:
```yaml
# app/config/messenger.yaml

framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - my_eventstore_transaction_middleware

services:
    my_eventstore_transaction_middleware:
        class: Prooph\Bundle\EventStore\Messenger\EventStoreTransactionMiddleware
        arguments:
            - '@my_transactional_event_store'
```
