<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Sprint;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class SprintContinue
{
    public function __invoke(Subscriptor $subscriptor): void
    {
        $subscriptor->watcher()->sprint()->continue();
    }
}
