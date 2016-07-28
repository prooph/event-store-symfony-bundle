<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare (strict_types = 1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Metadata;

class BlackHole implements \Prooph\EventStore\Metadata\MetadataEnricher
{
    public $valid = false;

    public function __construct()
    {
        $this->valid = true;
    }

    public function enrich(\Prooph\Common\Messaging\Message $message)
    {
    }
}
