<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Chronhub\Storm\Contracts\Reporter\Reporter;

interface MessageTracker extends Tracker
{
    /**
     * Shortcut to watch on Dispatch Event
     *
     * @see Reporter
     */
    public function onDispatch(callable $story, int $priority = 0): Listener;

    /**
     * Shortcut to watch on Finalize Event
     *
     * @see Reporter
     */
    public function onFinalize(callable $story, int $priority = 0): Listener;

    public function newStory(string $eventName): MessageStory;
}
