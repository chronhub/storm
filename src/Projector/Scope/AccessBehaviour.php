<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Support\Event\DecoratedEvent;
use Chronhub\Storm\Projector\Support\Event\GenericEvent;
use Chronhub\Storm\Reporter\DomainEvent;
use Illuminate\Support\Arr;

use function array_merge;
use function array_walk;
use function in_array;
use function is_array;
use function str_contains;

trait AccessBehaviour
{
    private ?DomainEvent $event = null;

    private ?DecoratedEvent $decoratedEvent = null;

    private ?array $state = null;

    private bool $isAcked = false;

    public function ack(string $event): ?self
    {
        $this->isAcked = true;

        return $event === $this->event::class ? $this : null;
    }

    public function ackOneOf(string ...$events): ?self
    {
        if (in_array($this->event::class, $events, true)) {
            $this->isAcked = true;

            return $this;
        }

        return null;
    }

    public function isOf(string $event): bool
    {
        return $event === $this->event::class;
    }

    public function incrementState(string $field = 'count', int $value = 1): static
    {
        $this->updateUserState($field, $value, true);

        return $this;
    }

    public function updateState(string $field = 'count', int|string $value = 1, bool $increment = false): static
    {
        $this->updateUserState($field, $value, $increment);

        return $this;
    }

    public function mergeState(string $field, mixed $value): static
    {
        $oldValue = $this->getFieldValue($field);

        $withMerge = is_array($oldValue) ? array_merge($oldValue, Arr::wrap($value)) : $value;

        Arr::set($this->state, $field, $withMerge);

        return $this;
    }

    public function when(bool $condition, null|callable|array $callback = null, null|callable|array $fallback = null): ?static
    {
        $callbacks = Arr::wrap($condition && $callback !== null ? $callback : $fallback);

        array_walk($callbacks, fn (callable $callback) => $callback($this));

        return $this;
    }

    public function stopWhen(bool $condition): static
    {
        if ($condition) {
            $this->stop();
        }

        return $this;
    }

    public function match(bool $condition): ?static
    {
        return $condition ? $this : null;
    }

    public function event(): DomainEvent
    {
        $this->assertEventIsAcked();

        return $this->event;
    }

    public function getState(): ?array
    {
        return $this->state;
    }

    /**
     * @internal
     */
    public function isAcked(): bool
    {
        return $this->isAcked;
    }

    /**
     * @internal
     */
    public function setEvent(DomainEvent $event): void
    {
        $this->event = $event;
    }

    /**
     * @internal
     */
    public function setState(?array $state): void
    {
        $this->state = $state;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->state[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->state[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->state[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->state[$offset]);
    }

    /**
     * @internal
     */
    public function finish(): void
    {
        $this->event = null;
        $this->decoratedEvent = null;
        $this->isAcked = false;
        $this->state = null;
    }

    public function __call(string $method, array $arguments): mixed
    {
        $this->assertEventIsAcked();

        if ($this->decoratedEvent === null) {
            $this->decoratedEvent = GenericEvent::fromEvent($this->event);
        }

        return $this->decoratedEvent->$method(...$arguments);
    }

    private function updateUserState(string $field, $value, bool $increment): void
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

    private function assertEventIsAcked(): void
    {
        if (! $this->isAcked) {
            throw new RuntimeException('Event must be acked before returning it');
        }
    }
}
