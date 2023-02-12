<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Reporter\DomainEvent;

interface TransactionalInMemoryChronicler extends InMemoryChronicler
{
    /**
     * Return pending events and clear cache
     *
     * @return array<DomainEvent>
     */
    public function pullUnpublishedEvents(): array;
}
