<?php
declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command\Fixture;

use Prooph\Bundle\EventStore\ProophEventStoreBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new ProophEventStoreBundle()
        ];
    }

    public function getLogDir(): string
    {
        return $this->getRootDir() . '/var/logs';
    }

    public function getCacheDir()
    {
        return $this->getRootDir() . '/var/cache';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/../../Resources/config/services.yml');
        $loader->load(__DIR__ . '/config/projections.yml');
    }
}
