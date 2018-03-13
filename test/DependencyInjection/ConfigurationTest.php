<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Prooph\Bundle\EventStore\DependencyInjection\Configuration;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\InMemoryEventStore;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\EventStore\BlackHole;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model\BlackHoleAggregate;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model\BlackHoleAggregateTranslator;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model\BlackHoleRepository;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\TodoProjection;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\TodoReadModel;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension()
    {
        return new ProophEventStoreExtension();
    }

    protected function getConfiguration()
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
                    'repositories' => [
                        'todo_list' => [
                            'repository_class' => BlackHoleRepository::class,
                            'aggregate_type' => BlackHoleAggregate::class,
                            'aggregate_translator' => BlackHoleAggregateTranslator::class,
                            'stream_name' => 'test',
                            'one_stream_per_aggregate' => true,
                            'snapshot_store' => 'prooph_test.bundle.snapshot_store.in_memory',
                        ],
                    ],
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
