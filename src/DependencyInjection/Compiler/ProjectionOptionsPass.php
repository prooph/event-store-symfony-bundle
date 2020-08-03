<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Bundle\EventStore\Projection\ProjectionOptions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ProjectionOptionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('prooph_event_store.projection_options_locator')) {
            return;
        }

        $projectionOptionsIds = \array_keys($container->findTaggedServiceIds(ProophEventStoreExtension::TAG_PROJECTION_OPTIONS));
        $projectionOptionsLocator = [];

        foreach ($projectionOptionsIds as $id) {
            $definition = $container->getDefinition($id);

            self::assertDefinitionIsAValidClass($id, $definition);

            $tags = $definition->getTag(ProophEventStoreExtension::TAG_PROJECTION_OPTIONS);
            foreach ($tags as $tag) {
                if (! isset($tag['projection_name'])) {
                    throw new RuntimeException(\sprintf(
                        '"projection_name" attribute is missing from tag "%s" on service "%s"',
                        ProophEventStoreExtension::TAG_PROJECTION_OPTIONS,
                        $id
                    ));
                }

                $projectionOptionsLocator[$tag['projection_name']] = new Reference($id);
            }
        }

        $locator = $container->getDefinition('prooph_event_store.projection_options_locator');
        $locator->replaceArgument(0, \array_merge($locator->getArgument(0), $projectionOptionsLocator));
    }

    /**
     * @param string $serviceId The id of the service that is verified
     * @param Definition $definition The Definition of the service that is verified
     *
     * @throws RuntimeException if the service does not implement ProjectionOptions.
     */
    private static function assertDefinitionIsAValidClass(string $serviceId, Definition $definition): void
    {
        /** @var object $definitionClass */
        $definitionClass = $definition->getClass();
        $reflection = new \ReflectionClass($definitionClass);

        if (! $reflection->implementsInterface(ProjectionOptions::class)) {
            throw new RuntimeException(\sprintf(
                'Tagged service "%s" must implement "%s"',
                $serviceId,
                ProjectionOptions::class
            ));
        }
    }
}
