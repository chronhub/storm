<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Reporter\DomainEvent;

interface EventPublisher
{
    /**
     * @param iterable{DomainEvent} $streamEvents
     */
    public function record(iterable $streamEvents): void;

    /**
     * @return iterable{DomainEvent}
     */
    public function pull(): iterable;

    /**
     * @param iterable{DomainEvent} $streamEvents
     */
    public function publish(iterable $streamEvents): void;

    public function flush(): void;
}
