<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Reporter\DomainEvent;

interface TransactionalInMemoryChronicler extends InMemoryChronicler
{
    /**
     * @return array{DomainEvent}|array
     */
    public function pullUnpublishedEvents(): array;
}
