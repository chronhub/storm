<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Projector\Repository\Event\ProjectionCreated;
use Chronhub\Storm\Projector\Repository\Event\ProjectionDeleted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionDeletedWithEvents;
use Chronhub\Storm\Projector\Repository\Event\ProjectionError;
use Chronhub\Storm\Projector\Repository\Event\ProjectionReset;
use Chronhub\Storm\Projector\Repository\Event\ProjectionRestarted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionStarted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionStopped;

use function array_keys;
use function array_merge;

final class EventMap
{
    private array $map = [
        ProjectionCreated::class => [],
        ProjectionStarted::class => [],
        ProjectionStopped::class => [],
        ProjectionRestarted::class => [],
        ProjectionReset::class => [],
        ProjectionDeleted::class => [],
        ProjectionDeletedWithEvents::class => [],
        ProjectionError::class => [],
    ];

    public function addListeners(string $event, array $listeners): void
    {
        $this->map[$event] = array_merge($this->map[$event], $listeners);
    }

    public function listeners(string $event): array
    {
        return $this->map[$event] ?? [];
    }

    public function events(): array
    {
        return array_keys($this->map);
    }

    public function map(): array
    {
        return $this->map;
    }
}
