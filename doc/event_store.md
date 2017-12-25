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

Do not be confused about the fact that the we defined a service with a class called event store – we are not done yet.
But we are ready to configure the event store:

```yaml
# app/config/config.yml or (flex) config/packages/prooph_event_store.yaml
prooph_event_store:
    stores:
        acme_store:
            event_store: 'prooph_event_store.pdo_mysql_event_store'
```

> **Hint**: To get autocompletion in some IDEs you can prepend the service id
> with an `@` (`'@prooph_event_store.pdo_mysql_event_store'`).
>
> The bundle will recognize this and find your event store anyway.

We configured our first event store.
To put data into the event store (and read them from it) we might want to add repositories.

## Adding repositories to the event store

If you are adding repositories to your event_store, you want to go for event sourcing.
Therefore we will explain how to add an event sourced repository.

First you need to install another package, prooph/event-sourcing](http://docs.getprooph.org/event-sourcing/):

```bash
composer require prooph/event-sourcing
```

Before we can start adding our repositories, we need to define another service:

```yaml
# app/config/config.yml or (flex) config/packages/prooph_event_store.yaml
services:
    prooph_event_sourcing.aggregate_translator:
        class: Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator
```

It will help our repository to translate the event stream into an aggregate and vice versa.

We assume that there is
 - a class `Acme\Prooph\Repository\EventStoreUserRepository` which extends `Prooph\EventSourcing\Aggregate\AggregateRepository`
 - and a class `Acme\Model\User` which extends `Prooph\EventSourcing\AggregateRoot`.
 
More information about Aggregate Roots and Aggregate Repositories can be found in the [official documentation](http://docs.getprooph.org/event-sourcing/).

Now we can configure our repository:

```yaml
# app/config/config.yml or (flex) config/packages/prooph_event_store.yaml
prooph_event_store:
    stores:
        acme_store:
            event_store: 'prooph_event_store.pdo_mysql_event_store'
            repositories:
                Acme\Prooph\Repository\EventStoreUserRepository:
                    aggregate_type: Acme\Model\User
                    aggregate_translator: 'prooph_event_sourcing.aggregate_translator'
```

> **Hint**: To get autocompletion in some IDEs you can prepend the service id
> with an `@` (`'@prooph_event_sourcing.aggregate_translator'`).
>
> The bundle will recognize this and find your event store anyway.

Now you can access the repository from the service container with the id `Acme\Prooph\Repository\EventStoreUserRepository`.

> **Hint**: If you do not want your repositories to have their classes as service ids,
> you can configure them like this:
> ```yaml
> # app/config/config.yml or (flex) config/packages/prooph_event_store.yaml
> prooph_event_store:
>     stores:
>         acme_store:
>             event_store: 'prooph_event_store.pdo_mysql_event_store'
>             repositories:
>                 acme.repository.prooph.event_store_user_repository:
>                     repository_class: Acme\Prooph\Repository\EventStoreUserRepository
>                     aggregate_type: Acme\Model\User
>                     aggregate_translator: 'prooph_event_sourcing.aggregate_translator'
> ```
> This way your repository will be accessible with the service id `acme.repository.prooph.event_store_user_repository`.

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

## Plugins

A prooph Event Store can be expanded using plugins.
If you want to know more about Event Store Plugins, please have a look at the [official documentation](http://docs.getprooph.org/event-store/event_store_plugins.html).

Adding plugins to an Event Store is really simple.
Let's assume that we already have a class implementing `Prooph\EventStore\Plugin\Plugin`
and that we have configured it as service:

```yaml
# app/config/services.yml
services:
    acme.prooph.plugins.example_plugin:
        class: Acme\Prooph\Plugins\ExamplePlugin
```

To attaching the plugin to an Event Store, we just need to tag it with `prooph_event_store.<STORE-NAME>.plugin`.
So if our Event Store is named `acme_store`, it would look like this:

```yaml
# app/config/services.yml
services:
    acme.prooph.plugins.example_plugin:
        class: Acme\Prooph\Plugins\ExamplePlugin
        tags:
            - { name: 'prooph_event_store.acme_store.plugin' }
```

If you cant to attach to plugin to multiple Event Stores, just tag it multiple times.
In the special case that you want to attach the plugin to **every** Event Store,
you can use the tag `prooph_event_store.plugin` instead.

## Metadata enricher

If you do not know what a metadata enricher is, please have a look at the official documentation of [Metadata enricher](http://docs.getprooph.org/event-store/event_store_plugins.html#3-3-4).

Let's assume that we want to add the issuer of an event to the metadata.
Our Metadata enricher might look like this:

```php
<?php

declare(strict_types=1);

namespace Acme\Prooph\MetadataEnricher;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class IssuerMetadataEnricher implements MetadataEnricher
{
    /** @var TokenStorageInterface */
    private $tokens;
    
    public function __construct(TokenStorageInterface $tokens)
    {
        $this->tokens = $tokens;
    }
    
    public function enrich(Message $message): Message
    {
        if ($this->tokens->getToken()) {
            $message = $message
                ->withAddedMetadata('issuer_type', 'user')
                ->withAddedMetadata('issuer_name', $this->tokens->getToken()->getUsername());
        }
        return $message;
    }
}
```

And our service definition like this:

```yaml
# app/config/services.yml
services:
    acme.prooph.metadata_enricher.issuer:
        class: Acme\Prooph\MetadataEnricher\IssuerMetadataEnricher
        arguments: ['@security.token_storage']
```

To enable the enricher for every Event Store, we just need to tag the service:

```yaml
# app/config/services.yml
services:
    acme.prooph.metadata_enricher.issuer:
        class: Acme\Prooph\MetadataEnricher\IssuerMetadataEnricher
        arguments: ['@security.token_storage']
        tags:
            - { name: 'prooph_event_store.metadata_enricher' }
```

But be careful, this tag would add the metadata enricher to **every** Event Store.
If you want to add it only to one store, you need to use the tag `prooph_event_store.<STORE-NAME>.metadata_enricher`.
