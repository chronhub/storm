<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use stdClass;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamEventConverter
{
    public function toArray(DomainEvent $event, bool $isAutoIncremented): array;

    public function toDomainEvent(iterable|stdClass $payload): DomainEvent;
}
