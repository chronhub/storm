<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Illuminate\Support\Collection;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;

interface InMemoryChronicler extends Chronicler
{
    /**
     * Return the streams collection
     *
     * @return Collection{StreamName, array<DomainEvent>}
     */
    public function streams(): Collection;
}
