<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface StreamTracker extends Tracker
{
    public function newStory(string $eventName): StreamStory;
}
