<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;

final class TransactionalEventChronicler extends EventChronicler implements TransactionalEventableChronicler
{
    public function __construct(TransactionalChronicler $chronicler, TransactionalStreamTracker $tracker)
    {
        ProvideEvents::withTransactionalEvent($chronicler, $tracker);

        parent::__construct($chronicler, $tracker);
    }

    public function beginTransaction(): void
    {
        /** @var TransactionalStreamStory $story */
        $story = $this->tracker->newStory(self::BEGIN_TRANSACTION_EVENT);

        $this->tracker->disclose($story);

        if ($story->hasTransactionAlreadyStarted()) {
            throw $story->exception();
        }
    }

    public function commitTransaction(): void
    {
        /** @var TransactionalStreamStory $story */
        $story = $this->tracker->newStory(self::COMMIT_TRANSACTION_EVENT);

        $this->tracker->disclose($story);

        if ($story->hasTransactionNotStarted()) {
            throw $story->exception();
        }
    }

    public function rollbackTransaction(): void
    {
        /** @var TransactionalStreamStory $story */
        $story = $this->tracker->newStory(self::ROLLBACK_TRANSACTION_EVENT);

        $this->tracker->disclose($story);

        if ($story->hasTransactionNotStarted()) {
            throw $story->exception();
        }
    }

    public function transactional(callable $callback): bool|array|string|int|float|object
    {
        /** @phpstan-ignore-next-line */
        return $this->chronicler->transactional($callback);
    }

    public function inTransaction(): bool
    {
        return $this->chronicler instanceof TransactionalChronicler && $this->chronicler->inTransaction();
    }
}
