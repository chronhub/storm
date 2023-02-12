<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use function array_shift;

class ReportCommand implements Reporter
{
    use HasConstructableReporter;

    /**
     * @var array<array|object>
     */
    protected array $queue = [];

    /**
     * Handle one message at a time
     *
     * @var bool
     */
    protected bool $isDispatching = false;

    public function relay(object|array $message): void
    {
        $this->queue[] = $message;

        if (! $this->isDispatching) {
            $this->isDispatching = true;

            try {
                while ($command = array_shift($this->queue)) {
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
}
