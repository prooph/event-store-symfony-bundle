<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection\Options;

use Prooph\Bundle\EventStore\Projection\ProjectionOptions as ProjectionOptionsInterface;

final class ProjectionOptions implements ProjectionOptionsInterface
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function options(): array
    {
        return $this->options;
    }
}
