<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

        $this->addEventStoreSection($root);
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

    /**
     * Add event store section to configuration tree
     *
     * @link https://github.com/prooph/event-store
     *
     * @param ArrayNodeDefinition $node
     */
    private function addEventStoreSection(ArrayNodeDefinition $node): void
    {
        $beginsWithAt = function ($v) {
            return \strpos($v, '@') === 0;
        };
        $removeFirstCharacter = function ($v) {
            return \substr($v, 1);
        };

        $node
            ->fixXmlConfig('store', 'stores')
            ->children()
            ->arrayNode('stores')
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
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
                ->end()
            ->end();
    }
}
