<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;

interface TransactionalChronicler extends Chronicler
{
    /**
     * @return void
     *
     * @throws TransactionAlreadyStarted
     */
    public function beginTransaction(): void;

    /**
     * @return void
     *
     * @throws TransactionNotStarted
     */
    public function commitTransaction(): void;

    /**
     * @return void
     *
     * @throws TransactionNotStarted
     */
    public function rollbackTransaction(): void;

    /**
     * @param  callable  $callback
     * @return bool|array|string|int|float|object
     *
     * @throws TransactionAlreadyStarted
     * @throws TransactionNotStarted
     */
    public function transactional(callable $callback): bool|array|string|int|float|object;

    /**
     * @return bool
     */
    public function inTransaction(): bool;
}
