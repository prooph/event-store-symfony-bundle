<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
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
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Defines and load event store instances.
 */
final class ProophEventStoreExtension extends Extension
{
    public const TAG_PROJECTION = 'prooph_event_store.projection';

    public function getNamespace(): string
    {
        return 'http://getprooph.org/schemas/symfony-dic/prooph';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('event_store.xml');

        self::loadProjectionManagers($config, $container);

        if (! empty($config['stores'])) {
            $this->loadEventStores(EventStore::class, $config, $container);
        }
    }

    private static function loadProjectionManagers(array $config, ContainerBuilder $container): void
    {
        $projectionManagers = [];
        $projectionManagersLocator = [];
        $projectionManagerForProjectionsLocator = [];
        $projectionsLocator = [];
        $readModelsLocator = [];

        foreach ($config['projection_managers'] as $projectionManagerName => $projectionManagerConfig) {
            $projectionManagerId = "prooph_event_store.projection_manager.$projectionManagerName";
            self::defineProjectionManager($container, $projectionManagerId, $projectionManagerConfig);

            [$projectionManagerForProjectionsLocator, $projectionsLocator, $readModelsLocator] = self::collectProjectionsForLocators(
                $projectionManagerConfig['projections'],
                $projectionManagerId,
                $projectionManagerForProjectionsLocator,
                $projectionsLocator,
                $readModelsLocator
            );

            $projectionManagers[$projectionManagerName] = "prooph_event_store.$projectionManagerName";
            $projectionManagersLocator[$projectionManagerName] = new Reference($projectionManagerId);
        }

        $container->setParameter('prooph_event_store.projection_managers', $projectionManagers);

        self::defineServiceLocator($container, 'prooph_event_store.projection_managers_locator', $projectionManagersLocator);
        self::defineServiceLocator($container, 'prooph_event_store.projection_manager_for_projections_locator', $projectionManagerForProjectionsLocator);
        self::defineServiceLocator($container, 'prooph_event_store.projection_read_models_locator', $readModelsLocator);
        self::defineServiceLocator($container, 'prooph_event_store.projections_locator', $projectionsLocator);
    }

    private static function defineProjectionManager(ContainerBuilder $container, string $serviceId, array $config): void
    {
        $projectionManagerDefinition = new ChildDefinition('prooph_event_store.projection_definition');
        $projectionManagerDefinition
            ->setFactory([new Reference('prooph_event_store.projection_factory'), 'createProjectionManager'])
            ->setArguments([
                new Reference($config['event_store']),
                isset($config['connection']) ? new Reference($config['connection']) : null,
                $config['event_streams_table'],
                $config['projections_table'],
            ]);

        $container->setDefinition($serviceId, $projectionManagerDefinition);
    }

    private static function defineServiceLocator(ContainerBuilder $container, string $id, array $serviceMap): void
    {
        $definition = new Definition(ServiceLocator::class, [$serviceMap]);
        $definition->addTag('container.service_locator');
        $container->setDefinition($id, $definition);
    }

    private static function collectProjectionsForLocators(
        array $projections,
        string $projectionManagerId,
        array $projectionManagerForProjectionsLocator,
        array $projectionsLocator,
        array $readModelsLocator
    ): array {
        foreach ($projections as $projectionName => $projectionConfig) {
            if (isset($projectionConfig['read_model'])) {
                $readModelsLocator[$projectionName] = new Reference($projectionConfig['read_model']);
            }

            $projectionsLocator[$projectionName] = new Reference($projectionConfig['projection']);
            $projectionManagerForProjectionsLocator[$projectionName] = new Reference($projectionManagerId);
        }

        return [$projectionManagerForProjectionsLocator, $projectionsLocator, $readModelsLocator];
    }

    /**
     * Loads event store configuration depending on type. For configuration examples, please take look at
     * test/DependencyInjection/Fixture/config files
     *
     * @param string           $class
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function loadEventStores(string $class, array $config, ContainerBuilder $container): void
    {
        $eventStores = [];

        foreach (\array_keys($config['stores']) as $name) {
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
    private function loadEventStore(string $name, array $options, ContainerBuilder $container): void
    {
        $eventStoreId = 'prooph_event_store.'.$name;
        $eventStoreDefinition = $container
            ->setDefinition(
                $eventStoreId,
                new ChildDefinition('prooph_event_store.store_definition')
            )
            ->setArguments(
                [
                    $name,
                    new Reference($options['event_store']),
                    new Reference('prooph_event_store.action_event_emitter_factory'),
                    $options['event_emitter'],
                    $options['wrap_action_event_emitter'],
                    new Reference('prooph_event_store.plugins_locator'),
                ]
            );

        if (! empty($options['repositories'])) {
            foreach ($options['repositories'] as $repositoryName => $repositoryConfig) {
                $repositoryClass = $repositoryConfig['repository_class'] ?? $repositoryName;

                if (! \class_exists($repositoryClass)) {
                    throw new RuntimeException(\sprintf(
                        'You must configure the class of repository "%s" either by configuring the \'repository_class\' key or by directly using the FQCN as the repository key.',
                        $repositoryClass
                    ));
                }

                $repositoryDefinition = $container
                    ->setDefinition(
                        $repositoryName,
                        new ChildDefinition('prooph_event_store.repository_definition')
                    )
                    ->setArguments(
                        [
                            $repositoryClass,
                            new Reference($eventStoreId),
                            $repositoryConfig['aggregate_type'],
                            new Reference($repositoryConfig['aggregate_translator']),
                            $repositoryConfig['snapshot_store'] ? new Reference($repositoryConfig['snapshot_store']) : null,
                            $repositoryConfig['stream_name'],
                            $repositoryConfig['one_stream_per_aggregate'],
                            $repositoryConfig['disable_identity_map'] ?? false,
                        ]
                    );
            }
        }

        // define metadata enrichers
        $metadataEnricherAggregateId = \sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', $name);

        $metadataEnricherAggregateDefinition = $container
            ->setDefinition(
                $metadataEnricherAggregateId,
                new ChildDefinition('prooph_event_store.metadata_enricher_aggregate_definition')
            );

        $metadataEnricherId = \sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $name);

        $metadataEnricherDefinition = $container
            ->setDefinition(
                $metadataEnricherId,
                new ChildDefinition('prooph_event_store.metadata_enricher_plugin_definition')
            );
    }
}
