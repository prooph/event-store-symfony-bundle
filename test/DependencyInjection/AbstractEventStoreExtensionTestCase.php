<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2024 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2024 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\ProophEventStoreBundle;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Projection\ProjectionManager;
use Prooph\EventStore\StreamName;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin\BlackHole as BlackHolePlugin;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;
use Symfony\Component\DependencyInjection\Dumper\YamlDumper;
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
    public function it_creates_an_event_store(): void
    {
        $container = $this->loadContainer('event_store');

        $config = $container->getDefinition('prooph_event_store.main_store');

        self::assertEquals(EventStore::class, $config->getClass());

        // @var $eventStore EventStore
        $eventStore = $container->get('prooph_event_store.main_store');
        self::assertInstanceOf(EventStore::class, $eventStore);

        $projectionManager = $container->get('prooph_event_store.projection_manager.main_projection_manager');
        self::assertInstanceOf(ProjectionManager::class, $projectionManager);
    }

    /** @test */
    public function it_creates_multiple_event_stores(): void
    {
        $container = $this->loadContainer('event_store_multiple');

        foreach (['main_store', 'second_store'] as $name) {
            $config = $container->getDefinition('prooph_event_store.' . $name);

            self::assertEquals(EventStore::class, $config->getClass());

            //* @var $eventStore EventStore */
            $eventStore = $container->get('prooph_event_store.' . $name);
            self::assertInstanceOf(EventStore::class, $eventStore);
        }
    }

    /** @test */
    public function it_dumps_multiple_event_stores(): void
    {
        $this->dump('event_store_multiple');
    }

    /** @test */
    public function it_can_attach_metadata_enrichers_to_every_event_store(): void
    {
        $message = $this->createMock(Message::class);
        $message
            ->expects($this->once())
            ->method('withAddedMetadata')
            ->with('global', true)
            ->willReturnSelf();

        $container = $this->loadContainer('metadata_enricher_global');
        // @var EventStore $store
        $store = $container->get('prooph_event_store.main_store');

        $store->appendTo(new StreamName('any'), new ArrayIterator([$message]));
    }

    /** @test */
    public function it_can_attach_metadata_enrichers_to_a_specific_event_store(): void
    {
        $enrichedMessage = $this->createMock(Message::class);
        $enrichedMessage
            ->expects($this->atLeastOnce())
            ->method('withAddedMetadata')
            ->with('specific', true)
            ->willReturnSelf();

        $notEnrichedMessage = $this->createMock(Message::class);
        $notEnrichedMessage->expects($this->never())->method('withAddedMetadata');

        $container = $this->loadContainer('metadata_enricher');
        // @var EventStore $withEnricherStore
        $withEnricherStore = $container->get('prooph_event_store.with_enricher_store');
        // @var EventStore $withoutEnricherStore
        $withoutEnricherStore = $container->get('prooph_event_store.without_enricher_store');

        $withEnricherStore->appendTo(new StreamName('any'), new ArrayIterator([$enrichedMessage]));
        $withoutEnricherStore->appendTo(new StreamName('any'), new ArrayIterator([$notEnrichedMessage]));
    }

    /** @test */
    public function it_can_attach_plugins_to_every_event_store(): void
    {
        $container = $this->loadContainer('plugins_global');

        /** @var BlackHolePlugin $plugin */
        $plugin = $container->get(BlackHolePlugin::class);
        $eventStore = $container->get('prooph_event_store.main_store');

        self::assertContains($eventStore, $plugin->stores);
    }

    /** @test */
    public function it_can_attach_plugins_to_a_specific_event_store(): void
    {
        $container = $this->loadContainer('plugins');

        /** @var BlackHolePlugin $plugin */
        $plugin = $container->get(BlackHolePlugin::class);
        $withPluginStore = $container->get('prooph_event_store.with_plugin_store');
        $withoutPluginStore = $container->get('prooph_event_store.without_plugin_store');

        self::assertContains($withPluginStore, $plugin->stores);
        $this->assertNotContains($withoutPluginStore, $plugin->stores);
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

    /** @test */
    public function it_dumps_an_event_stores_with_plugins()
    {
        $this->dump('plugins');
    }

    /** @test */
    public function it_dumps_an_event_stores_with_metadata_enrichers()
    {
        $this->dump('metadata_enricher');
    }

    /**
     * @test
     *
     *
     */
    public function it_expects_projection_nodes_to_have_a_projection_key(): void
    {
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

    private function dump(string $configFile)
    {
        $container = $this->loadContainer($configFile);
        $dumper = null;

        if ($this instanceof XmlEventStoreExtensionTest) {
            $dumper = new XmlDumper($container);
        } elseif ($this instanceof YamlEventStoreExtensionTest) {
            $dumper = new YamlDumper($container);
        }
        self::assertInstanceOf(Dumper::class, $dumper, \sprintf('Test type "%s" not supported', \get_class($this)));
        self::assertNotEmpty($dumper->dump());
    }
}
