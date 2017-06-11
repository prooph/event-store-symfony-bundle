<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection;

use Prooph\EventStore\Projection\ReadModelProjector;

interface ReadModelProjection
{
    public function project(ReadModelProjector $projector): ReadModelProjector;
}
