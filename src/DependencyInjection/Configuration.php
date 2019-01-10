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

use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ProophActionEventEmitter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    const PROOPH_EVENT_STORE_NODE = 'prooph_event_store';
    const PROJECTIONS_NODE = 'projections';
    const REPOSITORIES_NODE = 'repositories';

    /**
     * Normalizes XML config and defines config tree
     *
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::PROOPH_EVENT_STORE_NODE);
        $rootNode = $this->getRootNode($treeBuilder, self::PROOPH_EVENT_STORE_NODE);

        $this->addEventStoreSection($rootNode);
        $this->addProjectionManagerSection($rootNode);

        return $treeBuilder;
    }

    public function addProjectionManagerSection(ArrayNodeDefinition $node): void
    {
        $treeBuilder = new TreeBuilder(self::PROJECTIONS_NODE);
        $projectionsNode = $this->getRootNode($treeBuilder, self::PROJECTIONS_NODE);

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
                    ->scalarNode('projections_table')->defaultValue(self::PROJECTIONS_NODE)->end()
                    ->append($projectionsNode)
                ->end()
            ->end();
    }

    /**
     * Add event store section to configuration tree
     *
     * @link https://github.com/prooph/event-store
     *
     * @param ArrayNodeDefinition $node
     */
    private function addEventStoreSection(ArrayNodeDefinition $node): void
    {
        $treeBuilder = new TreeBuilder(self::REPOSITORIES_NODE);
        $repositoriesNode = $this->getRootNode($treeBuilder, self::REPOSITORIES_NODE);

        $beginsWithAt = function ($v) {
            return \strpos($v, '@') === 0;
        };
        $removeFirstCharacter = function ($v) {
            return \substr($v, 1);
        };

        /** @var ArrayNodeDefinition $repositoryNode */
        $repositoryNode = $repositoriesNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array');

        $repositoryNode
            ->children()
                ->scalarNode('repository_class')->end()
                ->scalarNode('aggregate_type')->isRequired()->end()
                ->scalarNode('aggregate_translator')->isRequired()
                    ->beforeNormalization()
                        ->ifTrue($beginsWithAt)
                        ->then($removeFirstCharacter)
                    ->end()
                ->end()
                ->scalarNode('snapshot_store')
                    ->defaultValue(null)
                    ->beforeNormalization()
                        ->ifTrue($beginsWithAt)
                        ->then($removeFirstCharacter)
                    ->end()
                ->end()
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
                ->fixXmlConfig('repository', self::REPOSITORIES_NODE)
                ->children()
                    ->scalarNode('event_emitter')
                        ->defaultValue(ProophActionEventEmitter::class)
                        ->validate()
                            ->ifTrue(function ($v) {
                                return ! \class_exists($v);
                            })
                            ->thenInvalid('Class %s does not exist')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return ! \in_array(ActionEventEmitter::class, \class_implements($v));
                            })
                            ->then(function ($v) {
                                throw new \InvalidArgumentException(\sprintf('%s must implement %s', $v, ActionEventEmitter::class));
                            })
                        ->end()
                    ->end()
                    ->booleanNode('wrap_action_event_emitter')->defaultValue(true)->end()
                    ->scalarNode('event_store')
                        ->isRequired()
                        ->beforeNormalization()
                            ->ifTrue($beginsWithAt)
                            ->then($removeFirstCharacter)
                        ->end()
                    ->end()
                    ->append($repositoriesNode)
                ->end()
            ->end();
    }

    /**
     * Gets the root node of a TreeBuilder with the appropriate way.
     *
     * @param TreeBuilder $treeBuilder
     * @param string $rootName
     *
     * @return ArrayNodeDefinition
     */
    private function getRootNode(TreeBuilder $treeBuilder, string $rootName): ArrayNodeDefinition
    {
        return \method_exists($treeBuilder, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root($rootName);
    }
}
