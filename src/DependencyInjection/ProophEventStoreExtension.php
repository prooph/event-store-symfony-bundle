<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection;

use Prooph\EventStore\EventStore;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class ProophEventStoreExtension extends Extension
{
    public const TAG_PROJECTION = 'prooph_event_store.projection';

    public const TAG_PROJECTION_OPTIONS = 'prooph_event_store.projection_options';

    public function getNamespace(): string
    {
        return 'http://getprooph.org/schemas/symfony-dic/prooph';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('event_store.xml');

        if (! empty($config['stores'])) {
            $this->loadEventStores($config, $container);
        }

        $this->loadProjectionManagers($config, $container);
    }

    private function loadProjectionManagers(array $config, ContainerBuilder $container): void
    {
        $projectionManagers = [];
        $projectionManagersLocator = [];
        $projectionManagerForProjectionsLocator = [];
        $projectionsLocator = [];
        $readModelsLocator = [];
        $projectionOptionsLocator = [];

        foreach ($config['projection_managers'] as $projectionManagerName => $projectionManagerConfig) {
            $projectionManagerId = "prooph_event_store.projection_manager.$projectionManagerName";
            $this->defineProjectionManager($container, $projectionManagerId, $projectionManagerConfig);

            [$projectionManagerForProjectionsLocator, $projectionsLocator, $readModelsLocator, $projectionOptionsLocator] = $this->collectProjectionsForLocators(
                $container,
                $projectionManagerConfig['projections'],
                $projectionManagerId,
                $projectionManagerForProjectionsLocator,
                $projectionsLocator,
                $readModelsLocator,
                $projectionOptionsLocator
            );

            $projectionManagers[$projectionManagerName] = "prooph_event_store.$projectionManagerName";
            $projectionManagersLocator[$projectionManagerName] = new Reference($projectionManagerId);
        }

        $container->setParameter('prooph_event_store.projection_managers', $projectionManagers);

        $this->defineServiceLocator($container, 'prooph_event_store.projection_managers_locator', $projectionManagersLocator);
        $this->defineServiceLocator($container, 'prooph_event_store.projection_manager_for_projections_locator', $projectionManagerForProjectionsLocator);
        $this->defineServiceLocator($container, 'prooph_event_store.projection_read_models_locator', $readModelsLocator);
        $this->defineServiceLocator($container, 'prooph_event_store.projections_locator', $projectionsLocator);
        $this->defineServiceLocator($container, 'prooph_event_store.projection_options_locator', $projectionOptionsLocator);
    }

    private function defineProjectionManager(ContainerBuilder $container, string $serviceId, array $config): void
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

    private function defineServiceLocator(ContainerBuilder $container, string $id, array $serviceMap): void
    {
        $definition = new Definition(ServiceLocator::class, [$serviceMap]);
        $definition->addTag('container.service_locator');
        $container->setDefinition($id, $definition);
    }

    private function collectProjectionsForLocators(
        ContainerBuilder $container,
        array $projections,
        string $projectionManagerId,
        array $projectionManagerForProjectionsLocator,
        array $projectionsLocator,
        array $readModelsLocator,
        array $projectionOptionsLocator
    ): array {
        foreach ($projections as $projectionName => $projectionConfig) {
            if (isset($projectionConfig['read_model'])) {
                $readModelsLocator[$projectionName] = new Reference($projectionConfig['read_model']);
            }

            $projectionsLocator[$projectionName] = new Reference($projectionConfig['projection']);
            $projectionManagerForProjectionsLocator[$projectionName] = new Reference($projectionManagerId);

            $projectionOptionsId = \sprintf('prooph_event_store.projection_options.%s', $projectionName);
            $this->defineProjectionOptions($container, $projectionOptionsId, $projectionConfig['options']);
            $projectionOptionsLocator[$projectionName] = new Reference($projectionOptionsId);
        }

        return [$projectionManagerForProjectionsLocator, $projectionsLocator, $readModelsLocator, $projectionOptionsLocator];
    }

    private function defineProjectionOptions(ContainerBuilder $container, string $serviceId, array $projectionOptions): void
    {
        $definition = new ChildDefinition('prooph_event_store.projection_options');
        $definition->setFactory([new Reference('prooph_event_store.projection_options_factory'), 'createProjectionOptions']);
        $definition->setArguments([$projectionOptions]);

        $container->setDefinition($serviceId, $definition);
    }

    /**
     * Loads event store configuration depending on type. For configuration examples, please take look at
     * test/DependencyInjection/Fixture/config files
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function loadEventStores(array $config, ContainerBuilder $container): void
    {
        $eventStores = [];

        foreach (\array_keys($config['stores']) as $name) {
            $eventStores[$name] = 'prooph_event_store.'.$name;
        }
        $container->setParameter('prooph_event_store.stores', $eventStores);

        $def = $container->getDefinition('prooph_event_store.store_definition');
        $def->setClass(EventStore::class);

        foreach ($config['stores'] as $name => $options) {
            $this->loadEventStore($name, $options, $container);
        }
    }

    private function loadEventStore(string $name, array $options, ContainerBuilder $container): void
    {
        $container
            ->setDefinition(
                \sprintf('prooph_event_store.%s', $name),
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

        // define metadata enrichers
        $container
            ->setDefinition(
                \sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', $name),
                new ChildDefinition('prooph_event_store.metadata_enricher_aggregate_definition')
            );

        $container
            ->setDefinition(
                \sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $name),
                new ChildDefinition('prooph_event_store.metadata_enricher_plugin_definition')
            );
    }
}
