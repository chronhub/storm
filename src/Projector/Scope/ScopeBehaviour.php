<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use Illuminate\Support\Arr;

use function array_merge;
use function array_walk;
use function in_array;
use function is_array;

trait ScopeBehaviour
{
    protected ?DomainEvent $event = null;

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
        $oldValue = data_get($this->state, $field);

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
        if (! $this->isAcked) {
            throw new RuntimeException('Event must be acked before returning it');
        }

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

    /**
     * @internal
     */
    public function __invoke(DomainEvent $event, ?array $state): Closure
    {
        $this->event = $event;
        $this->state = $state;

        return fn () => $this->reset();
    }

    private function reset(): void
    {
        $this->event = null;
        $this->state = null;
        $this->isAcked = false;
    }

    private function updateUserState(string $field, $value, bool $increment): void
    {
        $oldValue = data_get($this->state, $field);

        $withIncrement = $increment ? $oldValue + $value : $value;

        Arr::set($this->state, $field, $withIncrement);
    }
}
