<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare (strict_types = 1);

namespace Prooph\Bundle\EventStore\DependencyInjection;

use Prooph\EventStore\EventStore;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Defines and load event store instances.
 */
final class ProophEventStoreExtension extends Extension
{
    public function getNamespace()
    {
        return 'http://getprooph.org/schemas/symfony-dic/prooph';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('event_store.xml');

        if (!empty($config['stores'])) {
            $this->eventStoreLoad('store', EventStore::class, $config, $container);
        }

        $this->addClassesToCompile([
            EventStore::class,
        ]);
    }

    /**
     * Loads event store configuration depending on type. For configuration examples, please take look at
     * test/DependencyInjection/Fixture/config files
     *
     * @param string $type
     * @param string $class
     * @param array $config
     * @param ContainerBuilder $container
     * @param XmlFileLoader $loader
     */
    private function eventStoreLoad(
        string $type,
        string $class,
        array $config,
        ContainerBuilder $container
    ) {
        $typePlural = $type . 's';
        $eventStores = [];

        foreach (array_keys($config[$typePlural]) as $name) {
            $eventStores[$name] = sprintf('prooph_event_store.' . $type . '.%s_store', $name);
        }
        $container->setParameter('prooph_event_store.' . $typePlural, $eventStores);

        $def = $container->getDefinition('prooph_event_store.' . $type);
        $def->setClass($class);

        foreach ($config[$typePlural] as $name => $options) {
            $this->loadEventStore($type, $name, $options, $container);
        }
    }

    /**
     * Initializes specific event store class with plugins and metadata enricher. Each class dependency must be set
     * via a container or reference definition.
     *
     * @param string $type
     * @param string $name
     * @param array $options
     * @param ContainerBuilder $container
     * @throws \Symfony\Component\DependencyInjection\Exception\BadMethodCallException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Prooph\Bundle\EventStore\Exception\RuntimeException
     */
    private function loadEventStore(string $type, string $name, array $options, ContainerBuilder $container)
    {
        $eventStoreId = sprintf('prooph_event_store.%s.%s_store', $type, $name);
        $eventStoreDefinition = $container
            ->setDefinition(
                $eventStoreId,
                new DefinitionDecorator('prooph_event_store.' . $type)
            )
            ->setArguments(
                [
                    new Reference($options['adapter']),
                    new Reference($options['event_emitter']),
                ]
            );

        if (!empty($options['repositories'])) {
            foreach ($options['repositories'] as $repositoryName => $repositoryConfig) {
                $repositoryId = sprintf('prooph_event_store.%s.%s_repository', $type, $repositoryName);

                $repositoryDefinition = $container
                    ->setDefinition(
                        $repositoryId,
                        new DefinitionDecorator('prooph_event_store.repository')
                    )
                    ->setFactory([new Reference('prooph_event_store.repository_factory'), 'create'])
                    ->setArguments(
                        [
                            $repositoryConfig['repository_class'],
                            new Reference($eventStoreId),
                            $repositoryConfig['aggregate_type'],
                            new Reference($repositoryConfig['aggregate_translator']),
                            $repositoryConfig['snapshot_store'] ? new Reference($repositoryConfig['snapshot_store']) : null,
                            $repositoryConfig['stream_name'],
                            $repositoryConfig['one_stream_per_aggregate'],
                        ]
                    );
            }
        }

        // define metadata enrichers
        $metadataEnricherAggregateId = sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', $name);

        $metadataEnricherAggregateDefinition = $container
            ->setDefinition(
                $metadataEnricherAggregateId,
                new DefinitionDecorator('prooph_event_store.metadata_enricher_aggregate')
            )
            ->setClass('%prooph_event_store.metadata_enricher_aggregate.class%');

        $metadataEnricherId = sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $name);

        $metadataEnricherDefinition = $container
            ->setDefinition(
                $metadataEnricherId,
                new DefinitionDecorator('prooph_event_store.metadata_enricher_plugin')
            )
            ->setClass('%prooph_event_store.metadata_enricher_plugin.class%');
    }
}
