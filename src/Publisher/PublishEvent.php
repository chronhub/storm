<?php

declare(strict_types=1);

namespace Chronhub\Storm\Publisher;

use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use Chronhub\Storm\Contracts\Reporter\EventReporter;
use Illuminate\Support\Collection;

use function iterator_to_array;

final class PublishEvent implements EventPublisher
{
    private Collection $pendingEvents;

    public function __construct(private readonly EventReporter $reporter)
    {
        $this->pendingEvents = new Collection();
    }

    public function record(iterable $streamEvents): void
    {
        $this->pendingEvents = $this->pendingEvents->merge(iterator_to_array($streamEvents));
    }

    public function pull(): iterable
    {
        $pendingEvents = $this->pendingEvents;

        $this->flush();

        return $pendingEvents;
    }

    public function publish(iterable $streamEvents): void
    {
        foreach ($streamEvents as $streamEvent) {
            $this->reporter->relay($streamEvent);
        }
    }

    public function flush(): void
    {
        $this->pendingEvents = new Collection();
    }
}
