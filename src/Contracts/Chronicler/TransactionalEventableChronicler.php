<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface TransactionalEventableChronicler extends TransactionalChronicler
{
    public const BEGIN_TRANSACTION_EVENT = 'begin_transaction';

    public const COMMIT_TRANSACTION_EVENT = 'commit_transaction';

    public const ROLLBACK_TRANSACTION_EVENT = 'rollback_transaction';
}
