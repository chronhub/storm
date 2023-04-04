<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Chronhub\Storm\Reporter\DomainEvent;
use InvalidArgumentException;

interface AggregateType
{
    /**
     * @return class-string
     *
     * @throws InvalidArgumentException when aggregate root is not supported
     */
    public function from(string|DomainEvent|AggregateRoot $event): string;

    /**
     * @param  class-string  $aggregateRoot
     */
    public function isSupported(string $aggregateRoot): bool;

    /**
     * @param  class-string  $aggregate
     *
     * @throws InvalidArgumentException
     */
    public function assertAggregateIsSupported(string $aggregate): void;

    /**
     * @return class-string
     */
    public function current(): string;
}
