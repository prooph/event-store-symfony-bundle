<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection;

use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\EventStore\EventStore;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Defines and load event store instances.
 */
final class ProophEventStoreExtension extends Extension
{
    public const TAG_PROJECTION = 'prooph_event_store.projection';

    public function getNamespace()
    {
        return 'http://getprooph.org/schemas/symfony-dic/prooph';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('event_store.xml');

        if (! empty($config['projection_managers'])) {
            $this->loadProjectionManagers($config, $container);
        }

        if (! empty($config['stores'])) {
            $this->loadEventStores(EventStore::class, $config, $container);
        }
    }

    public function loadProjectionManagers(array $config, ContainerBuilder $container)
    {
        $projectionManagers = [];

        foreach ($config['projection_managers'] as $projectionManagerName => $projectionManagerConfig) {
            $projectionManagerDefintion = new ChildDefinition('prooph_event_store.projection_definition');
            $projectionManagerDefintion
                ->setFactory([new Reference('prooph_event_store.projection_factory'), 'createProjectionManager'])
                ->setArguments([
                    new Reference($projectionManagerConfig['event_store']),
                    isset($projectionManagerConfig['connection']) ? new Reference($projectionManagerConfig['connection']) : null,
                    $projectionManagerConfig['event_streams_table'],
                    $projectionManagerConfig['projections_table'],
                ]);

            $projectorManagerId = sprintf('prooph_event_store.projection_manager.%s', $projectionManagerName);
            $container->setDefinition(
                $projectorManagerId,
                $projectionManagerDefintion
            );

            $this->loadProjections($projectionManagerConfig, $projectionManagerName, $container);

            $projectionManagers[$projectionManagerName] = 'prooph_event_store.'.$projectionManagerName;
        }

        $container->setParameter('prooph_event_store.projection_managers', $projectionManagers);
    }

    public function loadProjections(array $config, string $projectionManager, ContainerBuilder $container)
    {
        foreach ($config['projections'] as $projectionName => $projectionConfig) {
            $tag = [
                'projection_name' => $projectionName,
                'projection_manager' => $projectionManager,
            ];

            if (isset($projectionConfig['read_model'])) {
                $tag['read_model'] = $projectionConfig['read_model'];
            }

            $container
                ->setDefinition(
                    sprintf('%s.%s', static::TAG_PROJECTION, $projectionName),
                    (new Definition())
                        ->setClass($projectionConfig['projection'])
                        ->addTag(static::TAG_PROJECTION, $tag)
                );
        }
    }

    /**
     * Loads event store configuration depending on type. For configuration examples, please take look at
     * test/DependencyInjection/Fixture/config files
     *
     * @param string           $class
     * @param array            $config
     * @param ContainerBuilder $container
     * @param XmlFileLoader    $loader
     */
    private function loadEventStores(
        string $class,
        array $config,
        ContainerBuilder $container
    ) {
        $eventStores = [];

        foreach (array_keys($config['stores']) as $name) {
            $eventStores[$name] = 'prooph_event_store.'.$name;
        }
        $container->setParameter('prooph_event_store.stores', $eventStores);

        $def = $container->getDefinition('prooph_event_store.store_definition');
        $def->setClass($class);

        foreach ($config['stores'] as $name => $options) {
            $this->loadEventStore($name, $options, $container);
        }
    }

    /**
     * Initializes specific event store class with plugins and metadata enricher. Each class dependency must be set
     * via a container or reference definition.
     *
     * @param string           $name
     * @param array            $options
     * @param ContainerBuilder $container
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\BadMethodCallException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Prooph\Bundle\EventStore\Exception\RuntimeException
     */
    private function loadEventStore(string $name, array $options, ContainerBuilder $container)
    {
        $eventStoreId = 'prooph_event_store.'.$name;
        $eventStoreDefinition = $container
            ->setDefinition(
                $eventStoreId,
                new ChildDefinition('prooph_event_store.store_definition')
            )
            // TODO: Remove me
            ->setPublic(true)
            ->setArguments(
                [
                    $name,
                    new Reference($options['event_store']),
                    new Reference('prooph_event_store.action_event_emitter_factory'),
                    $options['event_emitter'],
                    $options['wrap_action_event_emitter'],
                    new Reference('service_container'),
                ]
            );

        if (! empty($options['repositories'])) {
            foreach ($options['repositories'] as $repositoryName => $repositoryConfig) {
                $repositoryClass = $repositoryConfig['repository_class'] ?? $repositoryName;

                if (! class_exists($repositoryClass)) {
                    throw new RuntimeException(sprintf(
                        "Configure repository class using either passing FQCN as a key or 'repository_class' configuration key. Given: %s",
                        $repositoryClass
                    ));
                }

                $repositoryDefinition = $container
                    ->setDefinition(
                        $repositoryName,
                        new ChildDefinition('prooph_event_store.repository_definition')
                    )
                    // TODO: Remove me
                    ->setPublic(true)
                    ->setArguments(
                        [
                            $repositoryClass,
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
                new ChildDefinition('prooph_event_store.metadata_enricher_aggregate_definition')
            )
            // TODO: Remove me
            ->setPublic(true);

        $metadataEnricherId = sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $name);

        $metadataEnricherDefinition = $container
            ->setDefinition(
                $metadataEnricherId,
                new ChildDefinition('prooph_event_store.metadata_enricher_plugin_definition')
            )
            // TODO: Remove me
            ->setPublic(true);
    }
}
