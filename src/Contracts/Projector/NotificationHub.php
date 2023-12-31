<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Closure;

interface NotificationHub
{
    /**
     * Add hook
     *
     * @param class-string $hook
     */
    public function addHook(string $hook, callable $trigger): void;

    /**
     * Add hooks
     *
     * @param array<class-string, callable> $hooks
     */
    public function addHooks(array $hooks): void;

    /**
     * Trigger hook
     */
    public function trigger(object $hook): void;

    /**
     * Add listener
     */
    public function addListener(string $event, string|callable|array $callback): void;

    /**
     * Add listeners
     *
     * @param array<class-string, string|callable> $listeners
     */
    public function addListeners(array $listeners): void;

    /**
     * Forget event and all its callbacks
     */
    public function forgetListener(string $event): void;

    /**
     * Fire event and forget
     *
     * @param class-string|object $event
     */
    public function notify(string|object $event, mixed ...$arguments): void;

    /**
     * Fire many events and forget
     *
     * @param class-string|object ...$events
     */
    public function notifyMany(string|object ...$events): void;

    public function notifyWhen(bool $condition, string|object $event, ?Closure $onSuccess = null, ?Closure $fallback = null): self;

    /**
     * Fire event and wait for response
     *
     * @param class-string|object $event
     */
    public function expect(string|object $event, mixed ...$arguments): mixed;
}
