<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\QueryReporter;
use Chronhub\Storm\Reporter\Concern\HasConstructableReporter;
use React\Promise\PromiseInterface;

final class ReportQuery implements QueryReporter
{
    use HasConstructableReporter;

    public function relay(object|array $message): PromiseInterface
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $this->relayMessage($story);

        if ($story->hasException()) {
            throw $story->exception();
        }

        return $story->promise();
    }

    public function getType(): DomainType
    {
        return DomainType::QUERY;
    }
}
