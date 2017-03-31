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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('prooph_event_store');

        $rootNode
            ->children()
                ->arrayNode('pdo')
                ->isRequired()
                ->children()
                    ->scalarNode('dbname')->isRequired()->end()
                    ->scalarNode('host')->defaultValue('localhost')->end()
                    ->scalarNode('port')->isRequired()->end()
                    ->scalarNode('user')->defaultValue('root')->end()
                    ->scalarNode('password')->defaultNull()->end()
                    ->scalarNode('driver')->isRequired()->end()
                ->end()
            ->end();
        $this->addEventStoreSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Add event store section to configuration tree
     *
     * @link https://github.com/prooph/event-store
     *
     * @param ArrayNodeDefinition $node
     */
    private function addEventStoreSection(ArrayNodeDefinition $node)
    {
        $treeBuilder = new TreeBuilder();
        $repositoriesNode = $treeBuilder->root('repositories');

        /** @var $repositoriesNode ArrayNodeDefinition */
        $repositoryNode = $repositoriesNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array');

        $repositoryNode
            ->children()
                ->scalarNode('repository_class')->end()
                ->scalarNode('aggregate_type')->end()
                ->scalarNode('aggregate_translator')->end()
                ->scalarNode('snapshot_store')->defaultValue(null)->end()
                ->scalarNode('stream_name')->defaultValue(null)->end()
                ->booleanNode('one_stream_per_aggregate')->defaultValue(false)->end()
            ->end();

        $node
            ->fixXmlConfig('store', 'stores')
            ->children()
            ->arrayNode('stores')
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->fixXmlConfig('repository', 'repositories')
                ->children()
                    ->scalarNode('event_emitter')->defaultValue('prooph_event_store.action_event_emitter')->end()
                    ->scalarNode('type')->end()
                    ->append($repositoriesNode)
                ->end()
            ->end();
    }
}
