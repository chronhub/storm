<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface TransactionalStreamTracker extends StreamTracker
{
    public function newStory(string $eventName): TransactionalStreamStory;
}
