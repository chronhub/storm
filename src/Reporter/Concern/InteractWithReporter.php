<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Concern;

use React\Promise\PromiseInterface;
use function array_shift;

trait InteractWithReporter
{
    /**
     * @var array<array|object>
     */
    protected array $commandQueue = [];

    /**
     * Handle one message at a time
     */
    protected bool $isDispatching = false;

    protected function relayCommand(object|array $message): void
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

    protected function relayEvent(object|array $message): void
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $this->relayMessage($story);

        if ($story->hasException()) {
            throw $story->exception();
        }
    }

    protected function relayQuery(object|array $message): PromiseInterface
    {
        $story = $this->tracker->newStory(self::DISPATCH_EVENT);

        $story->withTransientMessage($message);

        $this->relayMessage($story);

        if ($story->hasException()) {
            throw $story->exception();
        }

        return $story->promise();
    }
}
