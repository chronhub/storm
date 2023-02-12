<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Illuminate\Support\Collection;
use Chronhub\Storm\Contracts\Tracker\Story;
use Chronhub\Storm\Contracts\Tracker\Listener;

trait InteractWithTracker
{
    private Collection $listeners;

    public function __construct()
    {
        $this->listeners = new Collection();
    }

    public function watch(string $eventName, callable $story, int $priority = 0): Listener
    {
        $listener = new GenericListener($eventName, $story, $priority);

        $this->listeners->push($listener);

        return $listener;
    }

    public function disclose(Story $story): void
    {
        $this->fireEvent($story, null);
    }

    public function discloseUntil(Story $story, callable $callback): void
    {
        $this->fireEvent($story, $callback);
    }

    public function forget(Listener $listener): void
    {
        $this->listeners = $this->listeners->reject(
            fn (Listener $subscriber): bool => $listener === $subscriber
        );
    }

    public function listeners(): Collection
    {
        return clone $this->listeners;
    }

    /**
     * Dispatch event and handle message
     */
    private function fireEvent(Story $story, ?callable $callback): void
    {
        $this->listeners
            ->filter(fn (Listener $listener): bool => $story->currentEvent() === $listener->eventName)
            ->sortByDesc(fn (Listener $listener): int => $listener->eventPriority, SORT_NUMERIC)
            ->each(function (Listener $listener) use ($story, $callback): bool {
                $listener->story()($story);

                if ($story->isStopped()) {
                    return false;
                }

                if ($callback && true === $callback($story)) {
                    return false;
                }

                return true;
            });
    }
}
