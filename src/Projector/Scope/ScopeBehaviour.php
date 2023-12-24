<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Support\Event\DecoratedEvent;
use Chronhub\Storm\Projector\Support\Event\GenericEvent;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use Illuminate\Support\Arr;

use function array_merge;
use function array_walk;
use function in_array;
use function is_array;
use function str_contains;

trait ScopeBehaviour
{
    protected ?DomainEvent $event = null;

    protected ?DecoratedEvent $decoratedEvent = null;

    protected ?array $state = null;

    protected bool $isAcked = false;

    public function ack(string $event): ?static
    {
        if ($this->isAcked) {
            return null;
        }

        if ($event === $this->event::class) {
            $this->isAcked = true;

            return $this;
        }

        return null;
    }

    public function ackOneOf(string ...$events): ?static
    {
        if (in_array($this->event::class, $events, true)) {
            return $this->ack($this->event::class);
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

    public function isAcked(): bool
    {
        return $this->isAcked;
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

    public function __call(string $method, array $arguments): mixed
    {
        // todo handle better exception
        $this->assertEventIsAcked();

        if ($this->decoratedEvent === null) {
            $this->decoratedEvent = GenericEvent::fromEvent($this->event);
        }

        return $this->decoratedEvent->$method(...$arguments);
    }

    public function __invoke(Management $management, DomainEvent $event, ?array $state): Closure
    {
        $this->setManagement($management);
        $this->event = $event;
        $this->state = $state;

        return fn () => $this->reset();
    }

    abstract protected function setManagement(Management $management): void;

    private function reset(): void
    {
        $this->event = null;
        $this->decoratedEvent = null;
        $this->state = null;
        $this->isAcked = false;
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
