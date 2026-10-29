<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2026 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2026 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Prooph\Bundle\EventStore\Command\AbstractProjectionCommand;
use Prooph\Bundle\EventStore\Command\ProjectionDeleteCommand;
use Prooph\Bundle\EventStore\Command\ProjectionNamesCommand;
use Prooph\Bundle\EventStore\Command\ProjectionResetCommand;
use Prooph\Bundle\EventStore\Command\ProjectionRunCommand;
use Prooph\Bundle\EventStore\Command\ProjectionStateCommand;
use Prooph\Bundle\EventStore\Command\ProjectionStopCommand;
use Prooph\Bundle\EventStore\Command\ProjectionStreamPositionsCommand;
use Prooph\Bundle\EventStore\Factory\DefaultActionEventEmitterFactory;
use Prooph\Bundle\EventStore\Factory\DefaultEventStoreFactory;
use Prooph\Bundle\EventStore\Factory\ProjectionManagerFactory;
use Prooph\Bundle\EventStore\Projection\Options\ProjectionOptions;
use Prooph\Bundle\EventStore\Projection\Options\ProjectionOptionsFactory;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataEnricherAggregate;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\Projection\ProjectionManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('prooph_event_store.stream_table_map', []);

    $services = $container->services();

    $services->set('prooph_event_store.store_definition', EventStore::class)
        ->abstract()
        ->public()
        ->factory([service('prooph_event_store.store_factory'), 'createEventStore']);

    $services->set('prooph_event_store.store_factory', DefaultEventStoreFactory::class);

    $services->set('prooph_event_store.plugin_definition', Plugin::class)
        ->abstract();

    $services->set('prooph_event_store.metadata_enricher_plugin_definition', MetadataEnricherPlugin::class)
        ->abstract();

    $services->set('prooph_event_store.metadata_enricher_aggregate_definition', MetadataEnricherAggregate::class)
        ->abstract();

    $services->set('prooph_event_store.projection_definition', ProjectionManager::class)
        ->abstract()
        ->public()
        ->factory([service('prooph_event_store.projection_factory'), 'createProjectionManager']);

    $services->set('prooph_event_store.projection_factory', ProjectionManagerFactory::class);

    $services->set('prooph_event_store.projection_options', ProjectionOptions::class)
        ->abstract()
        ->public();

    $services->set('prooph_event_store.projection_options_factory', ProjectionOptionsFactory::class);

    $services->set('prooph_event_store.action_event_emitter_factory', DefaultActionEventEmitterFactory::class);

    $services->set('prooph_event_store.action_event_emitter', ProophActionEventEmitter::class);

    $services->set('prooph_event_store.message_converter', NoOpMessageConverter::class);

    $services->set('prooph_event_store.message_factory', FQCNMessageFactory::class);

    $services->alias(MessageFactory::class, 'prooph_event_store.message_factory');

    $services->set(AbstractProjectionCommand::class)
        ->abstract()
        ->args([
            service('prooph_event_store.projection_manager_for_projections_locator'),
            service('prooph_event_store.projections_locator'),
            service('prooph_event_store.projection_read_models_locator'),
            service('prooph_event_store.projection_options_locator'),
        ]);

    $commands = [
        ProjectionDeleteCommand::class => 'event-store:projection:delete',
        ProjectionResetCommand::class => 'event-store:projection:reset',
        ProjectionRunCommand::class => 'event-store:projection:run',
        ProjectionStateCommand::class => 'event-store:projection:state',
        ProjectionStopCommand::class => 'event-store:projection:stop',
        ProjectionStreamPositionsCommand::class => 'event-store:projection:positions',
    ];

    foreach ($commands as $class => $name) {
        $services->set($class)
            ->parent(AbstractProjectionCommand::class)
            ->tag('console.command', ['command' => $name]);
    }

    $services->set(ProjectionNamesCommand::class)
        ->args([
            service('prooph_event_store.projection_managers_locator'),
            '%prooph_event_store.projection_managers%',
        ])
        ->tag('console.command', ['command' => 'event-store:projection:names']);
};
