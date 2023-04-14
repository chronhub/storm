<?php

declare(strict_types=1);

namespace Chronhub\Storm\Auth;

use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use React\Promise\PromiseInterface;

final class GuardQueryOnDispatch extends AbstractGuardQuery
{
    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onDispatch(function (MessageStory $story): void {
            $promise = $story->promise();

            if ($promise instanceof PromiseInterface) {
                $this->authorizeQuery($story);
            }

        }, OnDispatchPriority::GUARD_QUERY->value);
    }
}
