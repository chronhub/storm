<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamPersistence
{
    /**
     * Get the table name
     */
    public function tableName(StreamName $streamName): string;

    /**
     * Up table
     */
    public function up(string $tableName): ?callable;

    /**
     * Serialize domain event
     */
    public function serializeEvent(DomainEvent $event): array;

    /**
     * Check sequence no is auto incremented
     */
    public function isAutoIncremented(): bool;

    /**
     * Get the table index
     */
    public function indexName(string $tableName): ?string;
}
