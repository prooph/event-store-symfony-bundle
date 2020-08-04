<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\ProophEventStoreBundle;
use Prooph\EventStore\Projection\ProjectionManager;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

abstract class AbstractEventStoreExtensionTestCase extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    /** @test */
    public function it_does_not_process_compiler_passes_without_configured_store(): void
    {
        self::assertInstanceOf(ContainerBuilder::class, $this->loadContainer('unconfigured'));
    }

    /** @test */
    public function it_creates_a_projection_manager(): void
    {
        $container = $this->loadContainer('event_store');

        $projectionManager = $container->get('prooph_event_store.projection_manager.main_projection_manager');
        self::assertInstanceOf(ProjectionManager::class, $projectionManager);
    }

    /** @test */
    public function it_can_register_projections_centrally(): void
    {
        $container = $this->loadContainer('projections');
        $managerLocator = $container->get('test.prooph_event_store.projection_manager_for_projections_locator');
        $projectionsLocator = $container->get('test.prooph_event_store.projections_locator');
        $readModelLocator = $container->get('test.prooph_event_store.projection_read_models_locator');
        $projectionOptionsLocator = $container->get('test.prooph_event_store.projection_options_locator');

        self::assertTrue(
            $managerLocator->has('todo_projection'),
            'The manager for the todo_projection is not available through the dedicated service locator'
        );
        self::assertTrue(
            $projectionsLocator->has('todo_projection'),
            'The projection todo_projection is not available through the dedicated service locator'
        );
        self::assertTrue(
            $readModelLocator->has('todo_projection'),
            'The read model for the todo_projection is not available through the dedicated service locator'
        );
        self::assertTrue(
            $projectionOptionsLocator->has('todo_projection'),
            'The projection options for the todo_projection is not available through the dedicated service locator'
        );
    }

    /** @test */
    public function it_can_register_projections_using_tags(): void
    {
        $container = $this->loadContainer('projections');
        $projectionsLocator = $container->get('test.prooph_event_store.projections_locator');
        $readModelLocator = $container->get('test.prooph_event_store.projection_read_models_locator');

        self::assertTrue(
            $projectionsLocator->has('black_hole_projection'),
            'The projection black_hole_projection is not available through the dedicated service locator'
        );
        self::assertTrue(
            $readModelLocator->has('black_hole_projection'),
            'The read model for the black_hole_projection is not available through the dedicated service locator'
        );
    }

    /**
     * @test
     */
    public function it_expects_projection_nodes_to_have_a_projection_key(): void
    {
        $this->expectExceptionMessage('The child node "projection" at path "prooph_event_store.projection_managers.main_projection_manager.projections.todo_projection" must be configured.');
        $this->expectException(InvalidConfigurationException::class);
        $this->loadContainer('missing_projection_key');
    }

    private function loadContainer($fixture, CompilerPassInterface $compilerPass = null)
    {
        $container = $this->getContainer();

        $container->registerExtension(new ProophEventStoreExtension());

        $loadYml = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__) . '/Resources/config'));
        $loadYml->load('services.yml');

        $this->loadFromFile($container, $fixture);

        if (null !== $compilerPass) {
            $container->addCompilerPass($compilerPass);
        }

        $this->compileContainer($container);

        return $container;
    }

    private function getContainer(array $bundles = [])
    {
        $map = [];

        foreach ($bundles as $bundle) {
            require_once __DIR__ . '/Fixture/Bundles/' . $bundle . '/' . $bundle . '.php';

            $map[$bundle] = 'Fixture\\Bundles\\' . $bundle . '\\' . $bundle;
        }

        $map['ProophEventStoreBundle'] = \realpath(__DIR__ . '/../../src');

        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => $map,
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => \realpath(__DIR__ . '/../../src'),
        ]));
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $bundle = new ProophEventStoreBundle();
        $bundle->build($container);
        $container->getCompilerPassConfig()->setOptimizationPasses([
            \class_exists(ResolveChildDefinitionsPass::class)
                ? new ResolveChildDefinitionsPass()
                : new ResolveDefinitionTemplatesPass(),
        ]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);

        $container->compile();
    }
}
