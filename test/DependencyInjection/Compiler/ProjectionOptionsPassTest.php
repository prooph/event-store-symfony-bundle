<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\ProjectionOptionsPass;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Bundle\EventStore\Projection\ProjectionOptions;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\Options\BlackHoleProjectionOptions;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ProjectionOptionsPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ProjectionOptionsPass());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerEmptyServiceLocator('prooph_event_store.projection_options_locator');
    }

    /** @test */
    public function it_does_not_allow_inappropriate_classes(): void
    {
        $this->registerProjectionOptions(
            'foo.projection_options',
            ['projection_name' => 'foo'],
            \stdClass::class
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Tagged service "foo.projection_options" must implement "%s"',
            ProjectionOptions::class
        ));

        $this->compile();
    }

    /** @test */
    public function it_does_not_allow_tags_without_projection_name(): void
    {
        $this->registerProjectionOptions(
            'foo.projection_options',
            [],
            BlackHoleProjectionOptions::class
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            '"projection_name" attribute is missing from tag "prooph_event_store.projection_options" on service "foo.projection_options"'
        );

        $this->compile();
    }

    /** @test */
    public function it_registers_tagged_projection_optionss()
    {
        $this->registerProjectionOptions(
            'foo.projection_options',
            ['projection_name' => 'foo'],
            BlackHoleProjectionOptions::class
        );

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.projection_options_locator',
            0,
            ['foo' => new Reference('foo.projection_options')]
        );
    }

    private function registerEmptyServiceLocator(string $serviceId): void
    {
        $this->container
            ->setDefinition($serviceId, new Definition(ServiceLocator::class, [[]]))
            ->addTag('container.service_locator');
    }

    private function registerProjectionOptions(
        string $serviceId,
        array $attributes,
        string $class
    ): void {
        $definition = new Definition($class);
        $definition->addTag(ProophEventStoreExtension::TAG_PROJECTION_OPTIONS, $attributes);
        $this->setDefinition($serviceId, $definition);
    }
}
