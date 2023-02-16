<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Stringable;

interface AggregateIdentity extends Stringable
{
    /**
     * Create instance of aggregate id from string
     */
    public static function fromString(string $aggregateId): static;

    /**
     * Get unique id as string
     */
    public function toString(): string;

    /**
     * Check equality of two aggregate id instances
     */
    public function equalsTo(self $aggregateId): bool;
}
