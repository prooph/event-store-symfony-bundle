<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\EventStore;

use Iterator;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\ProjectionFactory;
use Prooph\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\QueryFactory;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ReadModelProjectionFactory;

class BlackHole implements EventStore
{
    public function updateStreamMetadata(\Prooph\EventStore\StreamName $streamName, array $newMetadata): void
    {
        // TODO: Implement updateStreamMetadata() method.
    }

    public function create(\Prooph\EventStore\Stream $stream): void
    {
        // TODO: Implement create() method.
    }

    public function appendTo(\Prooph\EventStore\StreamName $streamName, Iterator $streamEvents): void
    {
        // TODO: Implement appendTo() method.
    }

    public function delete(\Prooph\EventStore\StreamName $streamName): void
    {
        // TODO: Implement delete() method.
    }

    public function createQuery(QueryFactory $factory = null): Query
    {
        // TODO: Implement createQuery() method.
    }

    public function createProjection(
        string $name,
        ProjectionOptions $options = null,
        ProjectionFactory $factory = null
    ): Projection {
        // TODO: Implement createProjection() method.
    }

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        ProjectionOptions $options = null,
        ReadModelProjectionFactory $factory = null
    ): ReadModelProjection {
        // TODO: Implement createReadModelProjection() method.
    }

    public function getDefaultQueryFactory(): QueryFactory
    {
        // TODO: Implement getDefaultQueryFactory() method.
    }

    public function getDefaultProjectionFactory(): ProjectionFactory
    {
        // TODO: Implement getDefaultProjectionFactory() method.
    }

    public function getDefaultReadModelProjectionFactory(): ReadModelProjectionFactory
    {
        // TODO: Implement getDefaultReadModelProjectionFactory() method.
    }

    public function deleteProjection(string $name, bool $deleteEmittedEvents): void
    {
        // TODO: Implement deleteProjection() method.
    }

    public function resetProjection(string $name): void
    {
        // TODO: Implement resetProjection() method.
    }

    public function stopProjection(string $name): void
    {
        // TODO: Implement stopProjection() method.
    }

    public function fetchStreamMetadata(\Prooph\EventStore\StreamName $streamName): array
    {
        // TODO: Implement fetchStreamMetadata() method.
    }

    public function hasStream(\Prooph\EventStore\StreamName $streamName): bool
    {
        // TODO: Implement hasStream() method.
    }

    public function load(
        \Prooph\EventStore\StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        // TODO: Implement load() method.
    }

    public function loadReverse(
        \Prooph\EventStore\StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        // TODO: Implement loadReverse() method.
    }

    /**
     * @return \Prooph\EventStore\StreamName
     */
    public function fetchStreamNames(
        ?string $filter,
        bool $regex,
        ?MetadataMatcher $metadataMatcher,
        int $limit,
        int $offset
    ): array {
        // TODO: Implement fetchStreamNames() method.
    }

    /**
     * @return string[]
     */
    public function fetchCategoryNames(?string $filter, bool $regex, int $limit, int $offset): array
    {
        // TODO: Implement fetchCategoryNames() method.
    }

    /**
     * @return string[]
     */
    public function fetchProjectionNames(?string $filter, bool $regex, int $limit, int $offset): array
    {
        // TODO: Implement fetchProjectionNames() method.
    }
}
