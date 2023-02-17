<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;

trait DetachMessageListener
{
    /**
     * @var array<Listener>
     */
    protected array $messageListeners = [];

    public function detachFromReporter(MessageTracker $tracker): void
    {
        foreach ($this->messageListeners as $listener) {
            $tracker->forget($listener);
        }

        $this->messageListeners = [];
    }
}
