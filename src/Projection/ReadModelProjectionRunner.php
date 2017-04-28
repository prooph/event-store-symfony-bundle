<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection;

use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

interface ReadModelProjectionRunner
{
    public function project(ReadModelProjector $projector): ReadModelProjector;

    public function readModel() : ReadModel;
}
