<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithProjection;

    public function __construct(protected QuerySubscriptionInterface $subscription)
    {
    }

    public function run(bool $inBackground): void
    {
        $this->subscription->start($inBackground);
    }

    public function reset(): void
    {
        $this->subscription->streamManager()->resets();

        $this->subscription->initializeAgain();
    }
}
