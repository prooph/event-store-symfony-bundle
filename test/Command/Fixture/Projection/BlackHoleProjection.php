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

namespace ProophTest\Bundle\EventStore\Command\Fixture\Projection;

use Prooph\Bundle\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\Projector;

final class BlackHoleProjection implements Projection
{
    public function project(Projector $projector): Projector
    {
        return $projector
            ->fromAll()
            ->whenAny(function () {
            });
    }
}
