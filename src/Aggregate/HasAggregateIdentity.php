<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Symfony\Component\Uid\Uuid;

trait HasAggregateIdentity
{
    protected function __construct(public readonly Uuid $identifier)
    {
    }

    public static function fromString(string $aggregateId): static
    {
        return new static(Uuid::fromString($aggregateId));
    }

    public function equalsTo(AggregateIdentity $otherAggregateId): bool
    {
        if (static::class !== $otherAggregateId::class) {
            return false;
        }

        return $this->identifier->equals($otherAggregateId->identifier);
    }

    public function toString(): string
    {
        return $this->identifier->jsonSerialize();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
