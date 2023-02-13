<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use stdClass;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamEventConverter
{
    /**
     * @param  DomainEvent  $event
     * @param  bool  $isAutoIncremented
     * @return array
     */
    public function toArray(DomainEvent $event, bool $isAutoIncremented): array;

    /**
     * @param  iterable|stdClass  $payload
     * @return DomainEvent
     */
    public function toDomainEvent(iterable|stdClass $payload): DomainEvent;
}
