<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command\Fixture\Projection\Options;

use Prooph\Bundle\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Pdo\Projection\GapDetection;

final class BlackHoleProjectionOptions implements ProjectionOptions
{
    public function options(): array
    {
        return [
            'gap_detection' => new GapDetection([0, 5, 5, 10, 15, 25, 40, 65, 105]),
        ];
    }
}
