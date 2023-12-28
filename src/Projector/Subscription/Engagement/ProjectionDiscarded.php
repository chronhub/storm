<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Engagement;

final readonly class ProjectionDiscarded
{
    public function __construct(public bool $withEmittedEvents)
    {
    }
}
