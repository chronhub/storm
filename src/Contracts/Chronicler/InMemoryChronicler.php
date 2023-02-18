<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Illuminate\Support\Collection;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;

interface InMemoryChronicler extends Chronicler
{
    /**
     * @return Collection{StreamName, array<DomainEvent>}
     */
    public function getStreams(): Collection;
}
