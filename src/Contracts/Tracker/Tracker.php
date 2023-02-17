<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface Tracker
{
    /**
     * Add new listener to tracker
     */
    public function watch(string $eventName, callable $story, int $priority = 0): Listener;

    /**
     * Fire tracker story
     */
    public function disclose(Story $story): void;

    /**
     * Fire context till a true condition is return
     */
    public function discloseUntil(Story $story, callable $callback): void;

    /**
     * Remove listener given from tracker
     */
    public function forget(Listener $eventListener): void;

    /**
     * Return a clone iterable listeners
     *
     * Only meant for introspection
     *
     * @return iterable<Listener>
     */
    public function listeners(): iterable;
}
