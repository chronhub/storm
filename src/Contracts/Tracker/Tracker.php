<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface Tracker
{
    public function watch(string $eventName, callable $story, int $priority = 0): Listener;

    public function disclose(Story $story): void;

    /**
     * @template T in true|void
     * checkMe is this is correct
     *
     * @param callable(Story): (T) $callback
     */
    public function discloseUntil(Story $story, callable $callback): void;

    public function forget(Listener $eventListener): void;

    /**
     * @return iterable<Listener> a clone instance of listeners
     */
    public function listeners(): iterable;
}
