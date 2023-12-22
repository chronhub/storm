<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Contracts\Projector\StackedReadModel;
use Chronhub\Storm\Projector\Support\Event\DecoratedEvent;
use Chronhub\Storm\Projector\Support\Event\GenericEvent;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateTimeImmutable;
use Illuminate\Support\Arr;
use Symfony\Component\Uid\Uuid;

use function array_walk;
use function str_contains;

/**
 * @method string|Uuid              eventId()
 * @method string|DateTimeImmutable eventTime()
 * @method array                    eventContent()
 */
final class ReadModelAccess implements ArrayAccess, ReadModelScope
{
    private ?DomainEvent $event = null;

    private ?DecoratedEvent $decoratedEvent = null;

    private int $acked = 0;

    private ?array $state = null;

    public function __construct(private readonly ReadModelManagement $management)
    {
    }

    public function increment(string $field = 'count', int|float $value = 1): self
    {
        $this->updateState($field, $value, true);

        return $this;
    }

    public function update(string|Closure $field = 'count', int|float|string $value = 1, bool $increment = false): self
    {
        $this->updateState($field, $value, $increment);

        return $this;
    }

    private function updateState(string $field, $value, bool $increment): void
    {
        $oldValue = $this->getFieldValue($field);

        $withIncrement = $increment ? $oldValue + $value : $value;

        Arr::set($this->state, $field, $withIncrement);
    }

    private function getFieldValue(string $field): mixed
    {
        // fixMe is this really needed ?
        //  we also have to prevent dot notation in user state field
        return str_contains($field, '.') !== false
            ? Arr::get($this->state, $field)
            : $this->state[$field];
    }

    public function ack(string $event): ?self
    {
        $this->acked++;

        return $this->isOf($event) ? $this : null;
    }

    public function when(bool $condition, null|callable|array $callback = null, null|callable|array $fallback = null): ?self
    {
        $callbacks = Arr::wrap($condition && $callback !== null ? $callback : $fallback);

        array_walk($callbacks, fn (callable $callback) => $callback($this));

        return $this;
    }

    public function match(bool $condition): ?self
    {
        return $condition ? $this : null;
    }

    public function isOf(string $event): bool
    {
        return $event === $this->event::class;
    }

    public function stop(): void
    {
        $this->management->close();
    }

    public function stopWhen(bool $condition): self
    {
        if ($condition) {
            $this->management->close();
        }

        return $this;
    }

    public function readModel(): StackedReadModel
    {
        return $this->management->getReadModel();
    }

    public function stack(string $operation, ...$arguments): self
    {
        $this->management->getReadModel()->stack($operation, ...$arguments);

        return $this;
    }

    public function streamName(): string
    {
        return $this->management->getCurrentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->management->getClock();
    }

    public function setEvent(DomainEvent $event): void
    {
        $this->event = $event;
    }

    public function event(): DomainEvent
    {
        return $this->event;
    }

    public function setState(?array $state): void
    {
        $this->state = $state;
    }

    public function getState(): ?array
    {
        return $this->state;
    }

    public function getAcked(): int
    {
        return $this->acked;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->state[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->state[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->state[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->state[$offset]);
    }

    public function __call(string $method, array $arguments): mixed
    {
        if ($this->decoratedEvent === null) {
            $this->decoratedEvent = GenericEvent::fromEvent($this->event);
        }

        return $this->decoratedEvent->$method(...$arguments);
    }
}
