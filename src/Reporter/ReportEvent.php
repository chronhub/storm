<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\EventReporter;
use Chronhub\Storm\Reporter\Concern\HasConstructableReporter;

final class ReportEvent implements EventReporter
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

    public function getType(): DomainType
    {
        return DomainType::EVENT;
    }
}
