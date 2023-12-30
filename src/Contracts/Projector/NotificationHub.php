<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

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
    public function addListener(string $listener, string|callable $callback): void;

    /**
     * Add listeners
     *
     * @param array<class-string, string|callable> $listeners
     */
    public function addListeners(array $listeners): void;

    /**
     * Fire event and forget
     *
     * @param class-string|object $notification
     */
    public function notify(string|object $notification, mixed ...$arguments): void;

    /**
     * Fire event and wait for response
     *
     * @param class-string|object $notification
     */
    public function expect(string|object $notification, mixed ...$arguments): mixed;
}
