<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection;

use Prooph\Bundle\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\Projector;

final class BlackHoleProjection implements Projection
{
    public function project(Projector $projector): Projector
    {
    }
}
