<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Projection\Options;

use Prooph\Bundle\EventStore\Projection\ProjectionOptions as ProjectionOptionsInterface;

final class ProjectionOptions implements ProjectionOptionsInterface
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function options(): array
    {
        return $this->options;
    }
}
