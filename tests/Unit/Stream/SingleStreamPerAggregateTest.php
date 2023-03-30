<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Stream\SingleStreamPerAggregate;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

#[CoversClass(SingleStreamPerAggregate::class)]
final class SingleStreamPerAggregateTest extends UnitTestCase
{
    private AggregateIdentity $aggregateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateId = V4AggregateId::create();
    }

    public function testDetermineStreamName(): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertEquals(
            new StreamName('some_stream_name'),
            $streamProducer->toStreamName($this->aggregateId)
        );
    }

    #[DataProvider('provideEvents')]
    public function testProduceStream(iterable $events): void
    {
        $streamName = new StreamName('some_stream_name');
        $aggregateId = $this->createMock(AggregateIdentity::class);
        $aggregateId->expects($this->never())->method('toString');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $stream = new Stream(new StreamName('some_stream_name'), $events);

        $this->assertNotSame($streamName, $stream->name());
        $this->assertEquals($stream, $streamProducer->toStream($aggregateId, $events));
    }

    #[DataProvider('provideEventsForFirstCommit')]
    public function testFirstCommitIsAlwaysFalsy(DomainEvent $event): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertFalse($streamProducer->isFirstCommit($event));
    }

    #[Test]
    public function testStreamProducerIsAlwaysAutoIncremented(): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertTrue($streamProducer->isAutoIncremented());
    }

    public static function provideEvents(): Generator
    {
        yield [[]];
        yield [[SomeEvent::fromContent(['steph' => 'bug'])]];
    }

    public static function provideEventsForFirstCommit(): Generator
    {
        $command = SomeEvent::fromContent(['steph' => 'bug']);

        yield [$command->withHeader(EventHeader::AGGREGATE_VERSION, 1), true];
        yield [$command->withHeader(EventHeader::AGGREGATE_VERSION, 2), false];
        yield [$command->withHeader(EventHeader::AGGREGATE_VERSION, 20), false];
    }
}
