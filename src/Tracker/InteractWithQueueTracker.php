<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Contracts\Tracker\Story;
use SplPriorityQueue;
use function iterator_to_array;
use function uasort;

trait InteractWithQueueTracker
{
    private SplPriorityQueue $listeners;

    private array $eventNameMap;

    public function __construct()
    {
        $this->listeners = new SplPriorityQueue();
        $this->eventNameMap = [];
    }

    public function watch(string $eventName, callable $story, int $priority = 0): Listener
    {
        $priority += $this->listeners->count();

        $listener = new GenericListener($eventName, $story, $priority);

        $this->listeners->insert($listener, -$priority);

        if (! isset($this->eventNameMap[$eventName])) {
            $this->eventNameMap[$eventName] = [];
        }

        $this->eventNameMap[$eventName][] = $listener;

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
        $this->listeners->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

        while (! $this->listeners->isEmpty()) {
            $current = $this->listeners->extract();
            if ($current['data'] === $listener) {
                $this->listeners->setExtractFlags(SplPriorityQueue::EXTR_DATA);

                break;
            }
        }
    }

    public function listeners(): array
    {
        $listeners = iterator_to_array(clone $this->listeners);
        $listenerMap = [];

        foreach ($listeners as $listener) {
            $eventName = $listener->name();

            if (! isset($listenerMap[$eventName])) {
                $listenerMap[$eventName] = [];
            }

            $listenerMap[$eventName][] = $listener;
        }

        foreach ($listenerMap as &$eventNameListeners) {
            uasort($eventNameListeners, fn (Listener $a, Listener $b) => $a->priority() <=> $b->priority());
        }

        unset($eventNameListeners);

        return $listenerMap;
    }

    private function fireEvent(Story $story, ?callable $callback): void
    {
        $eventName = $story->currentEvent();

        if (isset($this->eventNameMap[$eventName])) {
            $listeners = $this->eventNameMap[$eventName];

            foreach ($listeners as $listener) {
                $listener->story()($story);

                if ($story->isStopped()) {
                    break;
                }

                if ($callback && true === $callback($story)) {
                    break;
                }
            }
        }
    }
}
