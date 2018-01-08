<?php
declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model;

use Prooph\EventStore\Projection\ReadModel;

final class BlackHoleReadModel implements ReadModel
{
    public function init(): void
    {

    }

    public function isInitialized(): bool
    {

    }

    public function reset(): void
    {

    }

    public function delete(): void
    {

    }

    public function stack(string $operation, ...$args): void
    {

    }

    public function persist(): void
    {

    }
}
