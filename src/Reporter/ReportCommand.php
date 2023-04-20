<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\CommandReporter;
use Chronhub\Storm\Reporter\Concern\HasConstructableReporter;
use Chronhub\Storm\Reporter\Concern\InteractWithReporter;

final class ReportCommand implements CommandReporter
{
    use HasConstructableReporter;
    use InteractWithReporter;

    public function relay(object|array $message): void
    {
        $this->relayCommand($message);
    }

    public function getType(): DomainType
    {
        return DomainType::COMMAND;
    }
}
