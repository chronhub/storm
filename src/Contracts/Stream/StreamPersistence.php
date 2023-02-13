<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Stream;

use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamPersistence
{
    /**
     * Get the table name
     *
     * @param  StreamName  $streamName
     * @return string
     */
    public function tableName(StreamName $streamName): string;

    /**
     * Up table
     *
     * @param  string  $tableName
     * @return callable|null
     */
    public function up(string $tableName): ?callable;

    /**
     * Serialize domain event
     *
     * @param  DomainEvent  $event
     * @return array
     */
    public function serializeEvent(DomainEvent $event): array;

    /**
     * Check sequence no is auto incremented
     *
     * @return bool
     */
    public function isAutoIncremented(): bool;

    /**
     * Get the table index
     *
     * @param  string  $tableName
     * @return string|null
     */
    public function indexName(string $tableName): ?string;
}
