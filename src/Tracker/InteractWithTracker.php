<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Contracts\Tracker\Story;
use Illuminate\Support\Collection;

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
            static fn (Listener $subscriber): bool => $listener === $subscriber
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
            ->filter(static fn (Listener $listener): bool => $story->currentEvent() === $listener->eventName)
            ->sortByDesc(static fn (Listener $listener): int => $listener->eventPriority, SORT_NUMERIC)
            ->each(static function (Listener $listener) use ($story, $callback): bool {
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
