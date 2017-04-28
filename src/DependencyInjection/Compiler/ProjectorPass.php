<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProjectorPass implements CompilerPassInterface
{
    const TAGNAME = 'prooph_event_store.projection';

    public function process(ContainerBuilder $container)
    {

        $projectors = $container->findTaggedServiceIds(static::TAGNAME);

        foreach ($projectors as $id => $projector) {
            $projectorDefinition = $container->getDefinition($id);

            $tags = $projectorDefinition->getTag(static::TAGNAME);
            foreach ($tags as $tag) {
                if (!isset($tag['projection_name'])) {
                    throw new RuntimeException(sprintf('"projection_name" argument is missing from on "prooph_event_store.projection" tagged service "%s"',
                        $id));
                }
                if (!isset($tag['read_model'])) {
                    throw new RuntimeException(sprintf('"read_model" argument is missing from on "prooph_event_store.projection" tagged service "%s"',
                        $id));
                }
                if (!isset($tag['projection_manager'])) {
                    throw new RuntimeException(sprintf('"event_store" argument is missing from on "prooph_event_store.projection" tagged service "%s"',
                        $id));
                }

                $projectorDefinition
                    ->setArguments([sprintf('projection:%s', $tag['projection_name'])])
                    ->addMethodCall('setDescription', [sprintf('%s Projection', $tag['projection_name'])])
                    ->addMethodCall('setProjectionName', [$tag['projection_name']])
                    ->addMethodCall('setReadModel', [new Reference($tag['read_model'])])
                    ->addMethodCall('setProjectionManager', [new Reference(sprintf('prooph_event_store.projection_manager.%s', $tag['projection_manager']))]);
            }
            $container->setDefinition(
                $id,
                $projectorDefinition
            );
            // AddConsoleCommandPass is called before, so we have to manually add the command
            $container->setParameter('console.command.ids', array_merge($container->getParameter('console.command.ids'), [$id]));
        }
    }
}
