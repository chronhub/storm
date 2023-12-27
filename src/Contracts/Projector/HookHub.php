<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface HookHub
{
    /**
     * @param class-string $hook
     */
    public function addHook(string $hook, callable $trigger): void;

    public function trigger(object $hook): void;

    /**
     * @param class-string|object $notification
     */
    public function interact(string|object $notification, mixed ...$arguments): mixed;
}
