<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Illuminate\Support\Collection;

interface InMemoryChronicler extends Chronicler
{
    /**
     * @return Collection{StreamName, array<DomainEvent>}
     */
    public function getStreams(): Collection;
}
