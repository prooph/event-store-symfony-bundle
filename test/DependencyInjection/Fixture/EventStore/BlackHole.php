<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\EventStore;

use Iterator;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

class BlackHole implements EventStore
{
    public function updateStreamMetadata(StreamName $streamName, array $newMetadata): void
    {
        // TODO: Implement updateStreamMetadata() method.
    }

    public function create(Stream $stream): void
    {
        // TODO: Implement create() method.
    }

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void
    {
        // TODO: Implement appendTo() method.
    }

    public function delete(StreamName $streamName): void
    {
        // TODO: Implement delete() method.
    }

    public function fetchStreamMetadata(StreamName $streamName): array
    {
        // TODO: Implement fetchStreamMetadata() method.
    }

    public function hasStream(StreamName $streamName): bool
    {
        // TODO: Implement hasStream() method.
    }

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        // TODO: Implement load() method.
    }

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = null,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Iterator {
        // TODO: Implement loadReverse() method.
    }

    /**
     * @return StreamName[]
     */
    public function fetchStreamNames(
        ?string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        // TODO: Implement fetchStreamNames() method.
    }

    /**
     * @return StreamName[]
     */
    public function fetchStreamNamesRegex(
        string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array {
        // TODO: Implement fetchStreamNamesRegex() method.
    }

    /**
     * @return string[]
     */
    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        // TODO: Implement fetchCategoryNames() method.
    }

    /**
     * @return string[]
     */
    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array
    {
        // TODO: Implement fetchCategoryNamesRegex() method.
    }
}
