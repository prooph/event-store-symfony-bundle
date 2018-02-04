<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\MetadataEnricherPass;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Metadata\StaticMetadataEnricher;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin\BlackHole;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MetadataEnricherPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MetadataEnricherPass());
    }

    /** @test */
    public function it_registers_enrichers()
    {
        $this->registerEventStore('foo');
        $this->registerEventStore('bar');

        $this->registerMetadataEnricher(null, StaticMetadataEnricher::class, 'global_enricher');
        $this->registerMetadataEnricher('foo', StaticMetadataEnricher::class);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.metadata_enricher_aggregate.foo',
            0,
            [
                new Reference('global_enricher'),
                new Reference(StaticMetadataEnricher::class),
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.metadata_enricher_aggregate.bar',
            0,
            [
                new Reference('global_enricher'),
            ]
        );
    }

    private function registerMetadataEnricher(?string $storeName, string $className, ?string $serviceId = null): Definition
    {
        $definition = new Definition($className);
        $definition->addTag('prooph_event_store.' . ($storeName ? $storeName . '.' : '') . 'metadata_enricher');
        $this->setDefinition($serviceId ?? $className, $definition);

        return $definition;
    }
}
