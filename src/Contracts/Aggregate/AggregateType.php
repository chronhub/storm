<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;

interface AggregateType
{
    /**
     * Determine aggregate root class from domain event, aggregate root instance or string
     *
     * @param  string|DomainEvent|AggregateRoot  $event
     * @return class-string
     *
     * @throws InvalidArgumentException when aggregate root is not supported
     */
    public function from(string|DomainEvent|AggregateRoot $event): string;

    /**
     * Check if aggregate root given is supported
     *
     * @param  class-string  $aggregateRoot
     * @return bool
     */
    public function isSupported(string $aggregateRoot): bool;

    /**
     * Return current aggregate root class name
     *
     * @return class-string
     */
    public function current(): string;
}
