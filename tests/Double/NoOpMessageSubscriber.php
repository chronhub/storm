<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Double;

use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;

final class NoOpMessageSubscriber implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(public readonly string $eventName,
                                public readonly int $priority)
    {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch($this->eventName, function (): void {
            //
        }, $this->priority);
    }
}
