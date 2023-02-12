<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType as Type;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use InvalidArgumentException;

final readonly class AggregateType implements Type
{
    public function __construct(private string $concrete,
                                private array $map = [])
    {
        if (! class_exists($concrete)) {
            throw new InvalidArgumentException('Aggregate root must be a valid class name');
        }

        foreach ($map as $className) {
            if (! is_a($className, $this->concrete, true)) {
                throw new InvalidArgumentException("Class $className must inherit from $concrete");
            }
        }
    }

    public function from(string|DomainEvent|AggregateRoot $event): string
    {
        if ($event instanceof AggregateRoot) {
            $this->assertAggregateIsSupported($event::class);

            return $event::class;
        }

        if ($event instanceof DomainEvent) {
            $aggregateType = $event->header(EventHeader::AGGREGATE_TYPE);

            $this->assertAggregateIsSupported($aggregateType);

            return $aggregateType;
        }

        $this->assertAggregateIsSupported($event);

        return $this->concrete;
    }

    public function isSupported(string $aggregateRoot): bool
    {
        if ($aggregateRoot === $this->concrete) {
            return true;
        }

        return in_array($aggregateRoot, $this->map, true);
    }

    /**
     * @param  string  $aggregate
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function assertAggregateIsSupported(string $aggregate): void
    {
        if (! $this->isSupported($aggregate)) {
            throw new InvalidArgumentException("Aggregate root $aggregate class is not supported");
        }
    }

    public function current(): string
    {
        return $this->concrete;
    }
}
