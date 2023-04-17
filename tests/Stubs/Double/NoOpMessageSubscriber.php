<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs\Double;

use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;

final class NoOpMessageSubscriber implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(
        public readonly string $eventName,
        public readonly int $priority
    ) {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->watch($this->eventName, static function (): void {
            //
        }, $this->priority);
    }
}
