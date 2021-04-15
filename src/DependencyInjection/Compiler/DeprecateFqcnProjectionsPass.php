<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class DeprecateFqcnProjectionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has('prooph_event_store.projections_locator')) {
            return;
        }

        $projections = \array_map(function ($argument) {
            if ($argument instanceof ServiceClosureArgument) {
                $argument = $argument->getValues()[0];
            }

            return (string) $argument;
        }, $container->getDefinition('prooph_event_store.projections_locator')->getArgument(0));

        foreach ($projections as $eachProjectionId) {
            if (! $container->has($eachProjectionId) && \class_exists($eachProjectionId)) {
                $container->setDefinition($eachProjectionId, new Definition($eachProjectionId));
                @\trigger_error(
                    "The service for the projection \"$eachProjectionId\" is not available. "
                    . 'Passing FQCN of projections to the configuration is deprecated since v0.5 '
                    . 'and the support for this will be removed with v1.0',
                    E_USER_DEPRECATED
                );
            }
        }
    }
}
