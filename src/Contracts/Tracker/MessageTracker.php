<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface MessageTracker extends Tracker
{
    /**
     * Create new story instance with given event
     */
    public function newStory(string $eventName): MessageStory;
}
