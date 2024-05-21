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

namespace Prooph\Bundle\EventStore;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\DeprecateFqcnProjectionsPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\MetadataEnricherPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\PluginLocatorPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\PluginsPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\ProjectionOptionsPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\RegisterProjectionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ProophEventStoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new MetadataEnricherPass());
        $container->addCompilerPass(new PluginsPass());
        $container->addCompilerPass(new RegisterProjectionsPass());
        $container->addCompilerPass(new ProjectionOptionsPass());
        $container->addCompilerPass(new PluginLocatorPass());
        $container->addCompilerPass(new DeprecateFqcnProjectionsPass());
    }
}
