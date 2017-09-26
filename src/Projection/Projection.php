<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection;

use Prooph\EventStore\Projection\Projector;

interface Projection
{
    public function project(Projector $projector): Projector;
}
