<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2024 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2024 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
