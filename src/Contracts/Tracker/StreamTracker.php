<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface StreamTracker extends Tracker
{
    /**
     * Create new stream tracker story
     *
     * @param  string  $eventName
     * @return StreamStory
     */
    public function newStory(string $eventName): StreamStory;
}
