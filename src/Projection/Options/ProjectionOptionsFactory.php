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

use Prooph\EventStore\Pdo\Projection\GapDetection;
use Prooph\EventStore\Pdo\Projection\PdoEventStoreProjector;

final class ProjectionOptionsFactory
{
    public static function createProjectionOptions(array $config): ProjectionOptions
    {
        \array_walk($config, ['self', 'mapOptions']);

        return new ProjectionOptions($config);
    }

    private static function mapOptions(&$value, string $key): void
    {
        if ($key === PdoEventStoreProjector::OPTION_GAP_DETECTION) {
            $value = self::createGapDetection($value);
        }
    }

    private static function createGapDetection(array $config): GapDetection
    {
        $retryConfig = $config['retry_config'] ?? null;
        $detectionWindow = $config['detection_window'] ? new \DateInterval($config['detection_window']) : null;

        return new GapDetection($retryConfig, $detectionWindow);
    }
}
