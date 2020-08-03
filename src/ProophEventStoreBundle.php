<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\DeprecateFqcnProjectionsPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\ProjectionOptionsPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\RegisterProjectionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ProophEventStoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterProjectionsPass());
        $container->addCompilerPass(new ProjectionOptionsPass());
        $container->addCompilerPass(new DeprecateFqcnProjectionsPass());
    }
}
