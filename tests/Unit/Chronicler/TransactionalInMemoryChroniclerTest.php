<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use RuntimeException;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\InMemory\TransactionalInMemoryChronicler;
use function array_merge;
use function random_bytes;

final class TransactionalInMemoryChroniclerTest extends UnitTestCase
{
    private TransactionalInMemoryChronicler $chronicler;

    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamName = new StreamName('operation');

        $this->chronicler = new TransactionalInMemoryChronicler(
            new InMemoryEventStream(),
            new DetermineStreamCategory()
        );
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $this->assertFalse($this->chronicler->hasStream($this->streamName));
        $this->assertEmpty($this->chronicler->getStreams());
        $this->assertEmpty($this->chronicler->cachedStreams());
        $this->assertFalse($this->chronicler->inTransaction());
    }

    /**
     * @test
     */
    public function it_first_commit_in_transaction(): void
    {
        $this->assertEmpty($this->chronicler->getStreams());
        $this->assertEmpty($this->chronicler->cachedStreams());
        $this->assertEmpty($this->chronicler->unpublishedEvents());

        $events = $this->generateEvent(1, 0);

        $this->assertFalse($this->chronicler->inTransaction());

        $this->chronicler->beginTransaction();

        $this->assertTrue($this->chronicler->inTransaction());

        $this->chronicler->firstCommit(new Stream($this->streamName, $events));

        $this->assertCount(0, $this->chronicler->getStreams());
        $this->assertCount(1, $this->chronicler->cachedStreams());
        $this->assertCount(0, $this->chronicler->unpublishedEvents());

        $this->chronicler->commitTransaction();

        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());
        $this->assertCount(1, $this->chronicler->unpublishedEvents());

        $this->chronicler->beginTransaction();

        $this->chronicler->firstCommit(new Stream(new StreamName('transaction')));

        $this->chronicler->commitTransaction();
        $this->assertCount(2, $this->chronicler->getStreams());

        $this->assertFalse($this->chronicler->inTransaction());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_stream_already_exists(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $this->chronicler->beginTransaction();

        $this->chronicler->firstCommit(new Stream($this->streamName));

        $this->chronicler->commitTransaction();

        $this->chronicler->firstCommit(new Stream($this->streamName));
    }

    /**
     * @test
     */
    public function it_raise_exception_on_first_commit_when_transaction_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->firstCommit(new Stream($this->streamName));
    }

    /**
     * @test
     */
    public function it_persist_in_transaction(): void
    {
        $this->assertEmpty($this->chronicler->getStreams());
        $this->assertEmpty($this->chronicler->cachedStreams());
        $this->assertEmpty($this->chronicler->unpublishedEvents());

        $events = $this->generateEvent(1, 0);

        $this->assertFalse($this->chronicler->inTransaction());

        $this->chronicler->beginTransaction();

        $this->assertTrue($this->chronicler->inTransaction());

        $this->chronicler->firstCommit(new Stream($this->streamName, $events));
        $this->chronicler->commitTransaction();

        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());
        $this->assertCount(1, $this->chronicler->unpublishedEvents());

        $this->chronicler->beginTransaction();

        $amendEvents = $this->generateEvent(1, 1);

        $this->chronicler->amend(new Stream($this->streamName, $amendEvents));

        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertCount(1, $this->chronicler->cachedStreams());
        $this->assertCount(1, $this->chronicler->unpublishedEvents());

        $this->chronicler->commitTransaction();

        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());
        $this->assertCount(2, $this->chronicler->unpublishedEvents());

        $this->assertFalse($this->chronicler->inTransaction());

        $this->assertEquals(array_merge($events, $amendEvents), $this->chronicler->unpublishedEvents());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_stream_not_found_on_persist_when_stream_not_in_event_stream_provider_and_not_in_cache(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->beginTransaction();

        $this->assertCount(0, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());

        $this->chronicler->amend(new Stream($this->streamName));

        $this->chronicler->commitTransaction();
    }

    /**
     * @test
     */
    public function it_raise_exception_if_transaction_not_started_on_persist(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->beginTransaction();
        $this->chronicler->firstCommit(new Stream($this->streamName));
        $this->chronicler->commitTransaction();

        $this->chronicler->amend(new Stream($this->streamName));
    }

    /**
     * @test
     */
    public function it_pull_events_to_be_published(): void
    {
        $events = $this->generateEvent(10, 0);

        $this->chronicler->beginTransaction();

        $this->chronicler->firstCommit(new Stream($this->streamName, $events));

        $this->chronicler->commitTransaction();

        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());
        $this->assertCount(10, $this->chronicler->unpublishedEvents());

        $unpublishedEvents = $this->chronicler->pullUnpublishedEvents();

        $this->assertEquals($events, $unpublishedEvents);

        $this->assertCount(0, $this->chronicler->unpublishedEvents());
    }

    /**
     * @test
     */
    public function it_handle_job_fully_transactional(): void
    {
        $result = $this->chronicler->transactional(function (TransactionalInMemoryChronicler $chronicler): int {
            $this->assertEmpty($chronicler->getStreams());
            $this->assertEmpty($chronicler->cachedStreams());
            $this->assertEmpty($chronicler->unpublishedEvents());

            $events = $this->generateEvent(1, 0);

            $this->assertTrue($this->chronicler->inTransaction());

            $this->chronicler->firstCommit(new Stream($this->streamName, $events));

            return 42;
        });

        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());
        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertFalse($this->chronicler->inTransaction());
        $this->assertEquals(42, $result);
    }

    /**
     * @test
     */
    public function it_handle_job_fully_transactional_and_rollback_transaction_on_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('something went wrong');

        $this->chronicler->beginTransaction();
        $this->chronicler->firstCommit(new Stream($this->streamName));
        $this->chronicler->commitTransaction();

        $this->chronicler->transactional(function (TransactionalInMemoryChronicler $chronicler): never {
            $chronicler->firstCommit(new Stream(new StreamName('transaction')));

            $this->assertCount(1, $this->chronicler->getStreams());
            $this->assertCount(1, $this->chronicler->cachedStreams());

            throw new RuntimeException('something went wrong');
        });

        $this->assertCount(1, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());
        $this->assertFalse($this->chronicler->inTransaction());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_transaction_already_started_when_begin(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler->beginTransaction();
        $this->chronicler->beginTransaction();
    }

    /**
     * @test
     */
    public function it_raise_exception_when_transaction_not_started_when_commit(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->commitTransaction();
    }

    /**
     * @test
     */
    public function it_raise_exception_when_transaction_not_started_when_rollback(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->rollbackTransaction();
    }

    private function generateEvent(int $limit, int $versionStartedAt): array
    {
        $aggregateId = V4AggregateId::create()->toString();
        $events = [];

        $v = $versionStartedAt;
        while ($v !== ($versionStartedAt + $limit)) {
            $headers = [
                EventHeader::INTERNAL_POSITION => $v + 1,
                EventHeader::AGGREGATE_VERSION => $v + 1,
                EventHeader::AGGREGATE_ID => $aggregateId,
            ];

            $events[] = (new SomeEvent(['password' => random_bytes(16)]))->withHeaders($headers);

            $v++;
        }

        return $events;
    }
}
