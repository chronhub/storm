<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private Subscriptor $subscriptor)
    {
    }

    public function close(): void
    {
        $this->subscriptor->stop();
    }

    public function getCurrentStreamName(): string
    {
        return $this->subscriptor->getStreamName();
    }
}
