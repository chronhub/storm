<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;

final class TransactionalEventDraft extends EventDraft implements TransactionalStreamStory
{
    public function hasTransactionNotStarted(): bool
    {
        return $this->exception instanceof TransactionNotStarted;
    }

    public function hasTransactionAlreadyStarted(): bool
    {
        return $this->exception instanceof TransactionAlreadyStarted;
    }
}
