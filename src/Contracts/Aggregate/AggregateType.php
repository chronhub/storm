<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;

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
     * @return class-string
     */
    public function current(): string;
}
