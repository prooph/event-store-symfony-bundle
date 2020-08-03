<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection\Options;

use Prooph\Bundle\EventStore\Projection\ProjectionOptions;

final class BlackHoleProjectionOptions implements ProjectionOptions
{
    public function options(): array
    {
        return [];
    }
}
