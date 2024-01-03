<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Notification\Management;

final readonly class ProjectionDiscarded
{
    public function __construct(public bool $withEmittedEvents)
    {
    }
}
