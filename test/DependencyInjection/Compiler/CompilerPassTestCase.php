<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Prooph\EventStore\InMemoryEventStore;
use Symfony\Component\DependencyInjection\Definition;

abstract class CompilerPassTestCase extends AbstractCompilerPassTestCase
{
    protected function registerEventStore(string $storeName): Definition
    {
        $storeServiceId = 'prooph_event_store.' . $storeName;

        $stores = [];

        if ($this->container->hasParameter('prooph_event_store.stores')) {
            $stores = $this->container->getParameter('prooph_event_store.stores');
        }

        $stores[$storeName] = $storeServiceId;
        $this->setParameter('prooph_event_store.stores', $stores);

        $eventStoreDefinition = new Definition(InMemoryEventStore::class);

        $this->setDefinition($storeServiceId, $eventStoreDefinition);

        $this->setDefinition(
            sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', $storeName),
            new Definition()
        );

        $this->setDefinition(
            sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $storeName),
            new Definition()
        );

        return $eventStoreDefinition;
    }
}
