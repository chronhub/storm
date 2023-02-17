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
     * Persist first commit
     *
     * @throws StreamAlreadyExists
     */
    public function firstCommit(Stream $stream): void;

    /**
     * Store stream events
     *
     * @throws StreamNotFound
     * @throws ConcurrencyException
     */
    public function amend(Stream $stream): void;

    /**
     * Delete stream by stream name
     *
     * @throws StreamNotFound
     */
    public function delete(StreamName $streamName): void;

    /**
     * Get the underlying event stream provider
     */
    public function getEventStreamProvider(): EventStreamProvider;
}
