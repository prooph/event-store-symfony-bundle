<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command\Fixture;

use Prooph\Bundle\EventStore\ProophEventStoreBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new ProophEventStoreBundle(),
        ];
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/test/Command/Fixture/var/logs';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/test/Command/Fixture/var/cache';
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddConsoleCommandPass());
        parent::build($container);
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/../../Resources/config/services.yml');
        $loader->load(__DIR__ . '/config/projections.yml');
    }
}
