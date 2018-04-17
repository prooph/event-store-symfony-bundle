<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\ProjectorPass;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Bundle\EventStore\Projection\Projection;
use Prooph\Bundle\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model\BlackHoleReadModel;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\BlackHoleProjection;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\BlackHoleReadModelProjection;
use stdClass;
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

        $this->registerEmptyServiceLocator('prooph_event_store.projection_manager_for_projections_locator');
        $this->registerEmptyServiceLocator('prooph_event_store.projection_read_models_locator');
        $this->registerEmptyServiceLocator('prooph_event_store.projections_locator');
    }

    /** @test */
    public function it_does_not_allow_inappropriate_classes(): void
    {
        $this->registerProjectionManager('foo');
        $this->registerProjection(
            'foo.projection',
            ['projection_name' => 'foo', 'projection_manager' => 'foo'],
            stdClass::class
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Tagged service "foo.projection" must implement either "%s" or "%s"',
            ReadModelProjection::class,
            Projection::class
        ));

        $this->compile();
    }

    /** @test */
    public function it_does_not_allow_read_model_projections_without_a_read_model(): void
    {
        $this->registerProjectionManager('foo');
        $this->registerProjection(
            'foo.projection',
            ['projection_name' => 'foo', 'projection_manager' => 'foo'],
            BlackHoleReadModelProjection::class
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"read_model" attribute is missing from on "prooph_event_store.projection" tagged service "foo.projection"');

        $this->compile();
    }

    /** @test */
    public function it_does_not_allow_tags_without_projection_name(): void
    {
        $this->registerProjectionManager('foo');
        $this->registerProjection(
            'foo.projection',
            ['projection_manager' => 'foo'],
            BlackHoleProjection::class
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            '"projection_name" attribute is missing from on "prooph_event_store.projection" tagged service "foo.projection"'
        );

        $this->compile();
    }

    /** @test */
    public function it_does_not_allow_tags_without_projection_manager(): void
    {
        $this->registerProjectionManager('foo');
        $this->registerProjection(
            'foo.projection',
            ['projection_name' => 'foo'],
            BlackHoleProjection::class
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            '"projection_manager" attribute is missing from on "prooph_event_store.projection" tagged service "foo.projection"'
        );

        $this->compile();
    }

    /** @test */
    public function it_does_not_allow_tags_with_a_missing_projection_manager(): void
    {
        $this->registerProjectionManager('foo');
        $this->registerProjection(
            'foo.projection',
            ['projection_name' => 'foo', 'projection_manager' => 'bar'],
            BlackHoleProjection::class
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Projection "foo.projection" has been tagged as projection for the manager "bar", '
            . 'but this projection manager does not exist. Please configure a projection manager "bar", '
            . 'in the prooph_event_store configuration'
        );

        $this->compile();
    }

    /** @test */
    public function it_registers_tagged_projections()
    {
        $this->registerProjectionManager('bar');
        $this->registerProjection(
            'foo.projection',
            ['projection_name' => 'foo', 'projection_manager' => 'bar'],
            BlackHoleProjection::class
        );

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projection_manager_for_projections_locator',
            0,
            ['foo' => new Reference('prooph_event_store.projection_manager.bar')]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projections_locator',
            0,
            ['foo' => new Reference('foo.projection')]
        );
    }

    /** @test */
    public function it_registers_tagged_read_model_projections()
    {
        $this->registerProjectionManager('bar');
        $this->registerReadModel('foo.read_model', BlackHoleReadModel::class);
        $this->registerProjection(
            'foo.projection',
            ['projection_name' => 'foo', 'projection_manager' => 'bar', 'read_model' => 'foo.read_model'],
            BlackHoleReadModelProjection::class
        );

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projection_manager_for_projections_locator',
            0,
            ['foo' => new Reference('prooph_event_store.projection_manager.bar')]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projection_read_models_locator',
            0,
            ['foo' => new Reference('foo.read_model')]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projections_locator',
            0,
            ['foo' => new Reference('foo.projection')]
        );
    }

    private function registerEmptyServiceLocator(string $serviceId): void
    {
        $this->container
            ->setDefinition($serviceId, new Definition(ServiceLocator::class, [[]]))
            ->addTag('container.service_locator');
    }

    private function registerProjectionManager(string $name): void
    {
        $definition = new Definition(InMemoryProjectionManager::class);
        $id = sprintf('prooph_event_store.projection_manager.%s', $name);
        $this->setDefinition($id, $definition);
    }

    private function registerProjection(
        string $serviceId,
        array $attributes,
        string $class
    ): void {
        $definition = new Definition($class);
        $definition->addTag(ProophEventStoreExtension::TAG_PROJECTION, $attributes);
        $this->setDefinition($serviceId, $definition);
    }

    private function registerReadModel(string $serviceId, string $class): void
    {
        $this->setDefinition($serviceId, new Definition($class));
    }
}
