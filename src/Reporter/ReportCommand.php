<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\CommandReporter;
use Chronhub\Storm\Reporter\Concern\HasConstructableReporter;
use function array_shift;

final class ReportCommand implements CommandReporter
{
    use HasConstructableReporter;

    /**
     * @var array<array|object>
     */
    protected array $commandQueue = [];

    /**
     * Handle one message at a time
     */
    protected bool $isDispatching = false;

    public function relay(object|array $message): void
    {
        $this->commandQueue[] = $message;

        if (! $this->isDispatching) {
            $this->isDispatching = true;

            try {
                while ($command = array_shift($this->commandQueue)) {
                    $story = $this->tracker->newStory(self::DISPATCH_EVENT);

                    $story->withTransientMessage($command);

                    $this->relayMessage($story);

                    if ($story->hasException()) {
                        throw $story->exception();
                    }
                }
            } finally {
                $this->isDispatching = false;
            }
        }
    }

    public function getType(): DomainType
    {
        return DomainType::COMMAND;
    }
}
