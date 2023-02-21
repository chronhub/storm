<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface TransactionalStreamStory extends StreamStory
{
    public function hasTransactionNotStarted(): bool;

    public function hasTransactionAlreadyStarted(): bool;
}
