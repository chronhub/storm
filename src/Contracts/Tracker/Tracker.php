<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface Tracker
{
    /**
     * Add new listener to tracker
     *
     * @param  string  $eventName
     * @param  callable  $story
     * @param  int  $priority
     * @return Listener
     */
    public function watch(string $eventName, callable $story, int $priority = 0): Listener;

    /**
     * Fire tracker story
     *
     * @param  Story  $story
     * @return void
     */
    public function disclose(Story $story): void;

    /**
     * Fire context till a true condition is return
     *
     * @param  Story  $story
     * @param  callable  $callback
     * @return void
     */
    public function discloseUntil(Story $story, callable $callback): void;

    /**
     * Remove listener given from tracker
     *
     * @param  Listener  $eventListener
     * @return void
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
