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

namespace ProophTest\Bundle\EventStore\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Prooph\Bundle\EventStore\DependencyInjection\Configuration;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\InMemoryEventStore;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\EventStore\BlackHole;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\TodoProjection;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\TodoReadModel;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension(): ExtensionInterface
    {
        return new ProophEventStoreExtension();
    }

    protected function getConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    /**
     * @test
     * @dataProvider provideConfigsToTestServicesWithAt
     */
    public function it_allows_to_prefix_services_with_an_at(string $configFile): void
    {
        $expectedConfiguration = [
            'stores' => [
                'main_store' => [
                    'event_store' => BlackHole::class,
                    'event_emitter' => ProophActionEventEmitter::class,
                    'wrap_action_event_emitter' => true,
                ],
            ],
            'projection_managers' => [
                'main_projection_manager' => [
                    'event_store' => InMemoryEventStore::class,
                    'connection' => 'connection',
                    'projections' => [
                        'todo_projection' => [
                            'read_model' => TodoReadModel::class,
                            'projection' => TodoProjection::class,
                            'options' => [],
                        ],
                    ],
                    'event_streams_table' => 'event_streams',
                    'projections_table' => 'projections',
                ],
            ],
        ];
        $this->assertProcessedConfigurationEquals($expectedConfiguration, [$configFile]);
    }

    public static function provideConfigsToTestServicesWithAt(): array
    {
        return [
            'xml' => [__DIR__ . '/Fixture/config/xml/event_store_with_@.xml'],
            'yml' => [__DIR__ . '/Fixture/config/yml/event_store_with_@.yml'],
        ];
    }
}
