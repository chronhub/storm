<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporter;

class ReportEvent implements Reporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): void
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $this->relayMessage($story);

        if ($story->hasException()) {
            throw $story->exception();
        }
    }
}
