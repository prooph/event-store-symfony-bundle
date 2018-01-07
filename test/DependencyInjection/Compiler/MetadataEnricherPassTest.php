<?php
declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\MetadataEnricherPass;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Metadata\BlackHole;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Metadata\GlobalBlackHole;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MetadataEnricherPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MetadataEnricherPass());
    }

    /**
     * @test
     */
    public function it_registers_plugins()
    {
        $this->registerEventStore('foo');
        $this->registerEventStore('bar');

        $this->registerMetadataEnricher(null, GlobalBlackHole::class);
        $this->registerMetadataEnricher('foo', BlackHole::class);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', 'foo'),
            0,
            [
                new Reference(GlobalBlackHole::class),
                new Reference(BlackHole::class)
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', 'bar'),
            0,
            [
                new Reference(GlobalBlackHole::class)
            ]
        );
    }

    private function registerMetadataEnricher(?string $storeName, string $className, ?string $serviceId = null): Definition
    {
        $definition = new Definition($className);
        $definition->addTag(
            sprintf('prooph_event_store.%smetadata_enricher', $storeName ? $storeName . '.' : '')
        );
        $this->setDefinition($serviceId ?? $className, $definition);

        return $definition;
    }
}
