<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\InMemory\AbstractInMemoryChronicler;
use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\TransactionalInMemoryChronicler;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use function array_merge;

#[CoversClass(TransactionalInMemoryChronicler::class)]
#[CoversClass(AbstractInMemoryChronicler::class)]
final class TransactionalInMemoryChroniclerTest extends UnitTestCase
{
    private TransactionalInMemoryChronicler $chronicler;

    private StreamName $streamName;

    protected function setUp(): void
    {
        $this->streamName = new StreamName('operation');

        $this->chronicler = new TransactionalInMemoryChronicler(
            new InMemoryEventStream(),
            new DetermineStreamCategory()
        );
    }

    public function testInstance(): void
    {
        $this->assertFalse($this->chronicler->hasStream($this->streamName));
        $this->assertEmpty($this->chronicler->getStreams());
        $this->assertEmpty($this->chronicler->cachedStreams());
        $this->assertFalse($this->chronicler->inTransaction());
    }

    public function testFirstCommitInTransaction(): void
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

    public function testStreamAlreadyExistOnFirstCommitInTransaction(): void
    {
        $this->expectException(StreamAlreadyExists::class);

        $this->chronicler->beginTransaction();

        $this->chronicler->firstCommit(new Stream($this->streamName));

        $this->chronicler->commitTransaction();

        $this->chronicler->firstCommit(new Stream($this->streamName));
    }

    public function testTransactionNotStartedOnFirstCommitInTransaction(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->firstCommit(new Stream($this->streamName));
    }

    public function testAmendStreamInTransaction(): void
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

    public function testStreamNotFoundRaisedOnAmendWhenCommitTransaction(): void
    {
        $this->expectException(StreamNotFound::class);

        $this->chronicler->beginTransaction();

        $this->assertCount(0, $this->chronicler->getStreams());
        $this->assertCount(0, $this->chronicler->cachedStreams());

        $this->chronicler->amend(new Stream($this->streamName));

        $this->chronicler->commitTransaction();
    }

    public function testTransactionNotStartedRaisedOnAmendWhenCommitTransaction(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->beginTransaction();
        $this->chronicler->firstCommit(new Stream($this->streamName));
        $this->chronicler->commitTransaction();

        $this->chronicler->amend(new Stream($this->streamName));
    }

    public function testPullUnpublishedEvents(): void
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

    public function testFullTransactional(): void
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

    public function testRollbackTransactionWhenExceptionRaisedOnFullTransactional(): void
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

    public function testTransactionAlreadyStarted(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler->beginTransaction();
        $this->chronicler->beginTransaction();
    }

    public function testTransactionNotStartedOnCommit(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->commitTransaction();
    }

    public function testTransactionNotStartedOnRollback(): void
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

            $events[] = (new SomeEvent(['foo' => 'bar']))->withHeaders($headers);

            $v++;
        }

        return $events;
    }
}
