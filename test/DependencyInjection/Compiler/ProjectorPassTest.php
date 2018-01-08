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

class ProjectorPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ProjectorPass());
    }

    /**
     * @test
     */
    public function it_validates_and_aliases_projectors()
    {
        $projectionServiceId = 'foo.read_model_projection';

        $attributes = [
            'projection_name' => 'foo',
            'projection_manager' => 'foo',
            'read_model' => 'foo.read_model'
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
    }

    private function registerProjectionManager(string $name): Definition
    {
        $definition = new Definition(InMemoryProjectionManager::class);
        $this->setDefinition(
            sprintf('prooph_event_store.projection_manager.%s', $name),
            $definition
        );

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
