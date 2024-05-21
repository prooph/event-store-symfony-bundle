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

namespace ProophTest\Bundle\EventStore\Projection\Options;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\Projection\Options\ProjectionOptions;
use Prooph\Bundle\EventStore\Projection\Options\ProjectionOptionsFactory;
use Prooph\EventStore\Pdo\Projection\GapDetection;

class ProjectionOptionsFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_projection_options_instance(): void
    {
        $config = [
            'cache_size' => 1000,
            'sleep' => 100000,
            'persist_block_size' => 1000,
            'lock_timeout_ms' => 1000,
            'trigger_pcntl_dispatch' => false,
            'update_lock_threshold' => 0,
            'gap_detection' => [
                'retry_config' => [0, 5, 10, 15, 30],
                'detection_window' => 'P5M',
            ],
        ];

        $options = ProjectionOptionsFactory::createProjectionOptions($config);

        $expected = new ProjectionOptions([
            'cache_size' => 1000,
            'sleep' => 100000,
            'persist_block_size' => 1000,
            'lock_timeout_ms' => 1000,
            'trigger_pcntl_dispatch' => false,
            'update_lock_threshold' => 0,
            'gap_detection' => new GapDetection([0, 5, 10, 15, 30], new \DateInterval('P5M')),
        ]);

        self::assertEquals($expected, $options);
    }
}
