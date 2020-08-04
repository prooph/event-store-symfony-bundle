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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * Normalizes XML config and defines config tree
     *
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('prooph_event_store');
        // Keep compatibility with symfony/config < 4.2
        if (! \method_exists($treeBuilder, 'getRootNode')) {
            $root = $treeBuilder->root('prooph_event_store');
        } else {
            $root = $treeBuilder->getRootNode();
        }

        $this->addProjectionManagerSection($root);

        return $treeBuilder;
    }

    public function addProjectionManagerSection(ArrayNodeDefinition $node): void
    {
        $treeBuilder = new TreeBuilder('projections');
        // Keep compatibility with symfony/config < 4.2
        if (! \method_exists($treeBuilder, 'getRootNode')) {
            $projectionsNode = $treeBuilder->root('projections');
        } else {
            $projectionsNode = $treeBuilder->getRootNode();
        }

        $beginsWithAt = function ($v) {
            return \strpos($v, '@') === 0;
        };
        $removeFirstCharacter = function ($v) {
            return \substr($v, 1);
        };
        $projectionsNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->scalarNode('read_model')
                    ->beforeNormalization()
                        ->ifTrue($beginsWithAt)
                        ->then($removeFirstCharacter)
                    ->end()
                ->end()
                ->scalarNode('projection')
                    ->isRequired()
                    ->beforeNormalization()
                        ->ifTrue($beginsWithAt)
                        ->then($removeFirstCharacter)
                    ->end()
                ->end()
                ->arrayNode('options')
                    ->canBeUnset()
                    ->addDefaultsIfNotSet()
                    ->treatFalseLike([])
                    ->treatNullLike([])
                    ->ignoreExtraKeys(false)
                ->end()
            ->end();

        $node
            ->children()
            ->arrayNode('projection_managers')
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->children()
                    ->scalarNode('event_store')
                        ->isRequired()
                        ->beforeNormalization()
                            ->ifTrue($beginsWithAt)
                            ->then($removeFirstCharacter)
                        ->end()
                    ->end()
                    ->scalarNode('connection')
                        ->beforeNormalization()
                            ->ifTrue($beginsWithAt)
                            ->then($removeFirstCharacter)
                        ->end()
                    ->end()
                    ->scalarNode('event_streams_table')->defaultValue('event_streams')->end()
                    ->scalarNode('projections_table')->defaultValue('projections')->end()
                    ->append($projectionsNode)
                ->end()
            ->end();
    }
}
