<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Bundle\EventStore\Projection\Projection;
use Prooph\Bundle\EventStore\Projection\ReadModelProjection;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterProjectionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('prooph_event_store.projection_read_models_locator')
            || ! $container->hasDefinition('prooph_event_store.projection_manager_for_projections_locator')
            || ! $container->hasDefinition('prooph_event_store.projections_locator')
        ) {
            return;
        }

        $projectionIds = \array_keys($container->findTaggedServiceIds(ProophEventStoreExtension::TAG_PROJECTION));

        $readModelsLocator = [];
        $projectionManagerForProjectionsLocator = [];
        $projectionsLocator = [];

        foreach ($projectionIds as $id) {
            $projectorDefinition = $container->getDefinition($id);
            /** @var object $projectorDefinitionClass */
            $projectorDefinitionClass = $projectorDefinition->getClass();
            $projectionClass = new ReflectionClass($projectorDefinitionClass);

            self::assertProjectionHasAValidClass($id, $projectionClass);

            $isReadModelProjector = $projectionClass->implementsInterface(ReadModelProjection::class);

            $tags = $projectorDefinition->getTag(ProophEventStoreExtension::TAG_PROJECTION);
            foreach ($tags as $tag) {
                self::assertProjectionTagHasAttribute($id, $tag, 'projection_name');
                self::assertProjectionTagHasAttribute($id, $tag, 'projection_manager');
                self::assertProjectionManagerExists($tag['projection_manager'], $id, $container);

                if ($isReadModelProjector) {
                    self::assertProjectionTagHasAttribute($id, $tag, 'read_model');
                    $readModelsLocator[$tag['projection_name']] = new Reference($tag['read_model']);
                }

                $projectionManagerForProjectionsLocator[$tag['projection_name']] = new Reference(
                    \sprintf('prooph_event_store.projection_manager.%s', $tag['projection_manager'])
                );
                $projectionsLocator[$tag['projection_name']] = new Reference($id);
            }
        }

        self::addServicesToLocator($container, 'prooph_event_store.projections_locator', $projectionsLocator);
        self::addServicesToLocator($container, 'prooph_event_store.projection_read_models_locator', $readModelsLocator);
        self::addServicesToLocator(
            $container,
            'prooph_event_store.projection_manager_for_projections_locator',
            $projectionManagerForProjectionsLocator
        );
    }

    private static function addServicesToLocator(
        ContainerBuilder $container,
        string $locatorId,
        array $serviceMap
    ): void {
        $definition = $container->getDefinition($locatorId);
        $definition->replaceArgument(0, \array_merge($serviceMap, $definition->getArgument(0)));
    }

    /**
     * @param string $serviceId The id of the service that is verified
     * @param ReflectionClass $projectionClass The Reflection of the service that is verified
     * @throws RuntimeException if the service does implement neither ReadModelProjection nor Projection.
     */
    private static function assertProjectionHasAValidClass(string $serviceId, ReflectionClass $projectionClass): void
    {
        if (! $projectionClass->implementsInterface(ReadModelProjection::class)
            && ! $projectionClass->implementsInterface(Projection::class)
        ) {
            throw new RuntimeException(\sprintf(
                'Tagged service "%s" must implement either "%s" or "%s"',
                $serviceId,
                ReadModelProjection::class,
                Projection::class
            ));
        }
    }

    /**
     * @param string $serviceId The id of the service whose tag is verified
     * @param array $tag The actual tag
     * @param string $attributeName The attribute that has to be available in the tag
     * @throws RuntimeException if the attribute is not available in the tag
     */
    private static function assertProjectionTagHasAttribute(string $serviceId, array $tag, string $attributeName): void
    {
        if (! isset($tag[$attributeName])) {
            throw new RuntimeException(\sprintf(
                '"%s" attribute is missing from tag "%s" on service "%s"',
                $attributeName,
                ProophEventStoreExtension::TAG_PROJECTION,
                $serviceId
            ));
        }
    }

    /**
     * @param string $name The name of the projection manager
     * @param string $taggedServiceId The projection service which has been tagged
     * @param ContainerBuilder $container
     * @throws RuntimeException if the projection manager does not exist
     */
    private static function assertProjectionManagerExists(
        string $name,
        string $taggedServiceId,
        ContainerBuilder $container
    ): void {
        if (! $container->has("prooph_event_store.projection_manager.$name")) {
            throw new RuntimeException(
                "Projection \"$taggedServiceId\" has been tagged as projection for the manager \"$name\", "
                . "but this projection manager does not exist. Please configure a projection manager \"$name\", "
                . 'in the prooph_event_store configuration'
            );
        }
    }
}
