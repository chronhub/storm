<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface TransactionalEventableChronicler extends TransactionalChronicler
{
    /**
     * @var string
     */
    public const BEGIN_TRANSACTION_EVENT = 'begin_transaction';

    /**
     * @var string
     */
    public const COMMIT_TRANSACTION_EVENT = 'commit_transaction';

    /**
     * @var string
     */
    public const ROLLBACK_TRANSACTION_EVENT = 'rollback_transaction';
}
