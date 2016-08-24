<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Bundle\EventStore\DependencyInjection\ProophEventStoreExtension;
use Prooph\Bundle\EventStore\ProophEventStoreBundle;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Snapshot\SnapshotStore;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model\BlackHoleRepository;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;
use Symfony\Component\DependencyInjection\Dumper\YamlDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

abstract class AbtractEventStoreExtensionTestCase extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    /**
     * @test
     */
    public function it_creates_an_event_store()
    {
        $container = $this->loadContainer('event_store');

        $config = $container->getDefinition('prooph_event_store.main_store');

        self::assertEquals(EventStore::class, $config->getClass());

        /* @var $eventStore EventStore */
        $eventStore = $container->get('prooph_event_store.main_store');
        self::assertInstanceOf(EventStore::class, $eventStore);

        $repository = $container->get('todo_list');
        self::assertInstanceOf(BlackHoleRepository::class, $repository);

        $snapshotter = $container->get('prooph_test.bundle.event_store.snapshotter');
        self::assertInstanceOf(SnapshotStore::class, $snapshotter);
    }

    /**
     * @test
     */
    public function it_creates_multiple_event_stores()
    {
        $container = $this->loadContainer('event_store_multiple');

        foreach (['main_store', 'second_store'] as $name) {
            $config = $container->getDefinition('prooph_event_store.' . $name);

            self::assertEquals(EventStore::class, $config->getClass());

            //* @var $eventStore EventStore */
            $eventStore = $container->get('prooph_event_store.' . $name);
            self::assertInstanceOf(EventStore::class, $eventStore);

            $repository = $container->get($name . '.todo_list');
            self::assertInstanceOf(BlackHoleRepository::class, $repository);
        }
    }

    /**
     * @test
     */
    public function it_dumps_multiple_event_stores()
    {
        $this->dump('event_store_multiple');
    }

    private function loadContainer($fixture, CompilerPassInterface $compilerPass = null)
    {
        $container = $this->getContainer();

        $container->registerExtension(new ProophEventStoreExtension());

        $loadYml = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
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

        $map['ProophEventStoreBundle'] = realpath(__DIR__ . '/../../src');

        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => $map,
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => realpath(__DIR__ . '/../../src'),
        ]));
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $bundle = new ProophEventStoreBundle();
        $bundle->build($container);
        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveDefinitionTemplatesPass()]);
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
        self::assertInstanceOf(Dumper::class, $dumper, sprintf('Test type "%s" not supported', get_class($this)));
        self::assertNotEmpty($dumper->dump());
    }
}
