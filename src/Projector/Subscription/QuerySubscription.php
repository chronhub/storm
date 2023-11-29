<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscription;

final readonly class QuerySubscription implements Subscription
{
    use InteractWithSubscription;

    public function __construct(
        protected GenericSubscription $subscription,
        protected Chronicler $chronicler,
    ) {
    }

    public function compose(ContextReaderInterface $context, ProjectorScope $projectorScope, bool $keepRunning): void
    {
        $this->subscription->compose($context, $projectorScope, $keepRunning);
    }
}
