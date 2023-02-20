<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

use stdClass;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamPersistence
{
    public function up(string $tableName): ?callable;

    public function tableName(StreamName $streamName): string;

    public function serialize(DomainEvent $event): array;

    public function toDomainEvent(iterable|stdClass $payload): DomainEvent;

    public function isAutoIncremented(): bool;
}
