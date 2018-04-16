<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\ProjectorPass;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model\BlackHoleReadModel;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\BlackHoleReadModelProjection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ProjectorPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ProjectorPass());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerEmptyServiceLocator('prooph_event_store.projection_managers_locator');
        $this->registerEmptyServiceLocator('prooph_event_store.projection_manager_for_projections_locator');
        $this->registerEmptyServiceLocator('prooph_event_store.projection_read_models_locator');
        $this->registerEmptyServiceLocator('prooph_event_store.projections_locator');
    }

    /** @test */
    public function it_validates_and_aliases_projectors(): void
    {
        $projectionServiceId = 'foo.read_model_projection';

        $attributes = [
            'projection_name' => 'foo',
            'projection_manager' => 'foo',
            'read_model' => 'foo.read_model',
        ];

        $this->registerProjectionManager('foo');
        $this->registerReadModel('foo.read_model');
        $this->registerReadModelProjection($projectionServiceId, $attributes);

        $this->compile();

        $this->assertContainerBuilderHasAlias(
            sprintf('%s.%s.read_model', ProophEventStoreExtension::TAG_PROJECTION, $attributes['projection_name']),
            $attributes['read_model']
        );

        $this->assertContainerBuilderHasAlias(
            sprintf('%s.%s.projection_manager', ProophEventStoreExtension::TAG_PROJECTION, $attributes['projection_name']),
            sprintf('prooph_event_store.projection_manager.%s', $attributes['projection_manager'])
        );

        $this->assertContainerBuilderHasAlias(
            sprintf('%s.%s', ProophEventStoreExtension::TAG_PROJECTION, $attributes['projection_name']),
            $projectionServiceId
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projection_manager_for_projections_locator',
            0,
            ['foo' => new Reference('prooph_event_store.projection_manager.foo')]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projections_locator',
            0,
            ['foo' => new Reference('foo.read_model_projection')]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projection_read_models_locator',
            0,
            ['foo' => new Reference('foo.read_model')]
        );
    }

    private function registerEmptyServiceLocator(string $serviceId): void
    {
        $this->container
            ->setDefinition($serviceId, new Definition(ServiceLocator::class, [[]]))
            ->addTag('container.service_locator');
    }

    private function registerProjectionManager(string $name): Definition
    {
        $definition = new Definition(InMemoryProjectionManager::class);
        $id = sprintf('prooph_event_store.projection_manager.%s', $name);
        $this->setDefinition($id, $definition);
        $managerLocatorDefinition = $this->container->getDefinition('prooph_event_store.projections_locator');
        $locatorMap = $managerLocatorDefinition->getArgument(0);
        $locatorMap[$name] = new Reference($id);
        $managerLocatorDefinition->replaceArgument(0, $locatorMap);

        return $definition;
    }

    private function registerReadModelProjection(string $serviceId, array $attributes): Definition
    {
        $definition = new Definition(BlackHoleReadModelProjection::class);
        $definition->addTag(ProophEventStoreExtension::TAG_PROJECTION, $attributes);
        $this->setDefinition(
            $serviceId,
            $definition
        );

        return $definition;
    }

    private function registerReadModel(string $serviceId): Definition
    {
        $definition = new Definition(BlackHoleReadModel::class);
        $this->setDefinition(
            $serviceId,
            $definition
        );

        return $definition;
    }
}
