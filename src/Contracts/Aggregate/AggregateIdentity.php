<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Stringable;

interface AggregateIdentity extends Stringable
{
    /**
     * Create instance of aggregate id from string
     *
     * @param  string  $aggregateId
     * @return static
     */
    public static function fromString(string $aggregateId): static;

    /**
     * Get unique id as string
     *
     * @return string
     */
    public function toString(): string;

    /**
     * Check equality of two aggregate id instances
     *
     * @param  self  $aggregateId
     * @return bool
     */
    public function equalsTo(self $aggregateId): bool;
}
