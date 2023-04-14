<?php

declare(strict_types=1);

namespace Chronhub\Storm\Auth;

use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\OnFinalizePriority;
use React\Promise\PromiseInterface;

final class GuardQueryOnFinalize extends AbstractGuardQuery
{
    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onFinalize(function (MessageStory $story): void {
            $promise = $story->promise();

            if ($promise instanceof PromiseInterface) {
                $promiseGuard = $promise->then(function (mixed $result) use ($story): mixed {
                    $this->authorizeQuery($story, $result);

                    return $result;
                });

                $story->withPromise($promiseGuard);
            }

        }, OnFinalizePriority::GUARD_QUERY->value);
    }
}
