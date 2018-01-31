<?php

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Projection;

use Prooph\EventStore\Projection\AbstractReadModel;

final class TodoReadModel extends AbstractReadModel
{

    public function init(): void
    {
    }

    public function isInitialized(): bool
    {
        return false;
    }

    public function reset(): void
    {
    }

    public function delete(): void
    {
    }
}
