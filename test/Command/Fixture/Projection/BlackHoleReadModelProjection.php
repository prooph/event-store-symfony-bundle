<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command\Fixture\Projection;

use Prooph\Bundle\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ReadModelProjector;

final class BlackHoleReadModelProjection implements ReadModelProjection
{
    public function project(ReadModelProjector $projector): ReadModelProjector
    {
        return $projector
            ->fromAll()
            ->whenAny(function () {
            });
    }
}
