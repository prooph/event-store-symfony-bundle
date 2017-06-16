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

        $this->addPdoSection($rootNode);
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

    /**
     * @param $rootNode
     */
    public function addPdoSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('pdo')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('dbname')->defaultValue('%database_name%')->end()
                    ->scalarNode('host')->defaultValue('%database_host%')->end()
                    ->scalarNode('port')->defaultValue('%database_port%')->end()
                    ->scalarNode('user')->defaultValue('%database_user%')->end()
                    ->scalarNode('password')->defaultValue('%database_password%')->end()
                    ->scalarNode('driver')->defaultValue('%database_driver%')->end()
                    ->end()
            ->end();
    }
}
