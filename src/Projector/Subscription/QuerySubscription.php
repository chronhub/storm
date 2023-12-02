<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;

final class QuerySubscription implements QuerySubscriptionInterface
{
    use InteractWithSubscription;

    protected Chronicler $chronicler;

    protected Sprint $sprint;

    protected ProjectionStateInterface $state;

    public function __construct(
        protected StreamManagerInterface $streamManager,
        protected ProjectionOption $option,
        protected SystemClock $clock,
        Chronicler $chronicler,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
    }
}
