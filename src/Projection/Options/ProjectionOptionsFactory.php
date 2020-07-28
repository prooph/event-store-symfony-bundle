<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection\Options;

final class ProjectionOptionsFactory
{
    public static function createProjectionOptions(array $options): ProjectionOptions
    {
        return new ProjectionOptions($options);
    }
}
