<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Notification\Stream;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class StreamProcessed
{
    public function __construct(public string $streamName)
    {
    }

    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->setProcessedStream($this->streamName);
    }
}
