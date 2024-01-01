<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class StreamEventAcked
{
    public function __construct(public string $event)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->ackedStream()->ack($this->event);
    }
}
