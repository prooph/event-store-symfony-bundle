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
    public const TAG_PROJECTION_OPTIONS = 'prooph_event_store.projection_options';

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
    }

    private static function loadProjectionManagers(array $config, ContainerBuilder $container): void
    {
        $projectionManagers = [];
        $projectionManagersLocator = [];
        $projectionManagerForProjectionsLocator = [];
        $projectionsLocator = [];
        $readModelsLocator = [];
        $projectionOptionsLocator = [];

        foreach ($config['projection_managers'] as $projectionManagerName => $projectionManagerConfig) {
            $projectionManagerId = "prooph_event_store.projection_manager.$projectionManagerName";
            self::defineProjectionManager($container, $projectionManagerId, $projectionManagerConfig);

            [$projectionManagerForProjectionsLocator, $projectionsLocator, $readModelsLocator, $projectionOptionsLocator] = self::collectProjectionsForLocators(
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

        self::defineServiceLocator($container, 'prooph_event_store.projection_managers_locator', $projectionManagersLocator);
        self::defineServiceLocator($container, 'prooph_event_store.projection_manager_for_projections_locator', $projectionManagerForProjectionsLocator);
        self::defineServiceLocator($container, 'prooph_event_store.projection_read_models_locator', $readModelsLocator);
        self::defineServiceLocator($container, 'prooph_event_store.projections_locator', $projectionsLocator);
        self::defineServiceLocator($container, 'prooph_event_store.projection_options_locator', $projectionOptionsLocator);
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
            self::defineProjectionOptions($container, $projectionOptionsId, $projectionConfig['options']);
            $projectionOptionsLocator[$projectionName] = new Reference($projectionOptionsId);
        }

        return [$projectionManagerForProjectionsLocator, $projectionsLocator, $readModelsLocator, $projectionOptionsLocator];
    }

    private static function defineProjectionOptions(ContainerBuilder $container, string $serviceId, array $projectionOptions): void
    {
        $definition = new ChildDefinition('prooph_event_store.projection_options');
        $definition->setFactory([new Reference('prooph_event_store.projection_options_factory'), 'createProjectionOptions']);
        $definition->setArguments([$projectionOptions]);

        $container->setDefinition($serviceId, $definition);
    }
}
