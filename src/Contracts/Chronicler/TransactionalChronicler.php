<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;

interface TransactionalChronicler extends Chronicler
{
    /**
     * @throws TransactionAlreadyStarted
     */
    public function beginTransaction(): void;

    /**
     * @throws TransactionNotStarted
     */
    public function commitTransaction(): void;

    /**
     * @throws TransactionNotStarted
     */
    public function rollbackTransaction(): void;

    /**
     * @throws TransactionAlreadyStarted
     * @throws TransactionNotStarted
     */
    public function transactional(callable $callback): bool|array|string|int|float|object;

    public function inTransaction(): bool;
}
