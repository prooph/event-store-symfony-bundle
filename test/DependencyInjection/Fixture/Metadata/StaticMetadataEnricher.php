<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Metadata;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Metadata\MetadataEnricher;

final class StaticMetadataEnricher implements MetadataEnricher
{
    /** @var string */
    private $param;

    /** @var bool */
    private $value;

    public function __construct(string $param, bool $value)
    {
        $this->param = $param;
        $this->value = $value;
    }

    public function enrich(Message $message): Message
    {
        return $message->withAddedMetadata($this->param, $this->value);
    }
}
