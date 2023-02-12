<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface TransactionalStreamStory extends StreamStory
{
    /**
     * Check if exception is a transaction not started
     *
     * @return bool
     */
    public function hasTransactionNotStarted(): bool;

    /**
     * Check if exception is a transaction already started
     *
     * @return bool
     */
    public function hasTransactionAlreadyStarted(): bool;
}
