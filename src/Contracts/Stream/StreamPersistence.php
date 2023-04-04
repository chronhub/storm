<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;

interface StreamPersistence
{
    public function up(string $tableName): ?callable;

    public function tableName(StreamName $streamName): string;

    public function serialize(DomainEvent $event): array;

    public function isAutoIncremented(): bool;
}
