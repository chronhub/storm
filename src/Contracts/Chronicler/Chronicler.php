<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;

interface Chronicler extends ReadOnlyChronicler
{
    /**
     * @throws StreamAlreadyExists
     */
    public function firstCommit(Stream $stream): void;

    /**
     * @throws StreamNotFound
     * @throws ConcurrencyException
     */
    public function amend(Stream $stream): void;

    /**
     * @throws StreamNotFound
     */
    public function delete(StreamName $streamName): void;

    public function getEventStreamProvider(): EventStreamProvider;
}
