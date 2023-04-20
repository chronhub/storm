<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\QueryReporter;
use Chronhub\Storm\Reporter\Concern\HasConstructableReporter;
use Chronhub\Storm\Reporter\Concern\InteractWithReporter;
use React\Promise\PromiseInterface;

final class ReportQuery implements QueryReporter
{
    use HasConstructableReporter;
    use InteractWithReporter;

    public function relay(object|array $message): PromiseInterface
    {
      return $this->relayQuery($message);
    }

    public function getType(): DomainType
    {
        return DomainType::QUERY;
    }
}
