<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerConnection extends Chronicler
{
    /**
     * Check either we under first commit persistence or amend
     * Required for decorated event store to handle the right exception code
     */
    public function isDuringCreation(): bool;
}
