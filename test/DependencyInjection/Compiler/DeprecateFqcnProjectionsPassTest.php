<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\DeprecateFqcnProjectionsPass;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\BlackHoleProjection;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @covers \Prooph\Bundle\EventStore\DependencyInjection\Compiler\DeprecateFqcnProjectionsPass
 */
class DeprecateFqcnProjectionsPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DeprecateFqcnProjectionsPass());
    }

    /** @test */
    public function it_does_not_warn_about_existing_projections(): void
    {
        $this->registerProjectionsServiceLocator(['foo' => BlackHoleProjection::class]);
        $this->container->setDefinition(BlackHoleProjection::class, new Definition(BlackHoleProjection::class));
        $this->compile();

        $this->addToAssertionCount(1); // no error has been triggered
    }

    /** @test */
    public function it_does_nothing_if_a_projection_does_not_exist_but_is_no_class(): void
    {
        $this->registerProjectionsServiceLocator(['foo' => 'service.id.that.is.no.class']);
        $this->compile();

        $this->addToAssertionCount(1); // no error has been triggered
    }

    /** @test */
    public function it_registers_projections_that_are_missing_and_provided_as_valid_class(): void
    {
        $this->registerProjectionsServiceLocator(['foo' => BlackHoleProjection::class]);
        $this->compile();

        $this->assertContainerBuilderHasService(BlackHoleProjection::class, BlackHoleProjection::class);
    }

    private function registerProjectionsServiceLocator(array $serviceIds): void
    {
        $serviceIds = array_map(function (string $serviceId) {
            return new ServiceClosureArgument(new Reference($serviceId));
        }, $serviceIds);
        $this->container->setDefinition(
            'prooph_event_store.projections_locator',
            new Definition(ServiceLocator::class, [$serviceIds])
        )->addTag('container.service_locator');
    }
}
