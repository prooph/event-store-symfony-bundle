<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection;

interface ProjectionOptions
{
    public function options(): array;
}
