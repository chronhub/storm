<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface TransactionalStreamTracker extends StreamTracker
{
    /**
     * Create new transactional stream tracker story
     *
     * @param  string  $eventName
     * @return TransactionalStreamStory
     */
    public function newStory(string $eventName): TransactionalStreamStory;
}
