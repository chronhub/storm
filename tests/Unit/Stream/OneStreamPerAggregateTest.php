<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\OneStreamPerAggregate;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(OneStreamPerAggregate::class)]
final class OneStreamPerAggregateTest extends UnitTestCase
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

        $streamProducer = new OneStreamPerAggregate($streamName);

        $this->assertEquals(
            new StreamName('some_stream_name-'.$this->aggregateId),
            $streamProducer->toStreamName($this->aggregateId)
        );
    }

    #[DataProvider('provideEvents')]
    public function testProduceStream(iterable $events): void
    {
        $streamName = new StreamName('some_stream_name');
        $aggregateId = $this->createMock(AggregateIdentity::class);
        $aggregateId->expects($this->once())->method('toString')->willReturn($this->aggregateId->toString());

        $streamProducer = new OneStreamPerAggregate($streamName);

        $stream = new Stream(new StreamName('some_stream_name-'.$this->aggregateId->toString()), $events);

        $this->assertNotSame($streamName, $stream->name());
        $this->assertEquals($stream, $streamProducer->toStream($aggregateId, $events));
    }

    #[DataProvider('provideEventsForFirstCommit')]
    public function testFirstStreamEventDiscerned(DomainEvent $event, bool $isFirstCommit): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new OneStreamPerAggregate($streamName);

        $this->assertEquals($isFirstCommit, $streamProducer->isFirstCommit($event));
    }

    #[Test]
    public function testStreamProducerIsNeverAutoIncremented(): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new OneStreamPerAggregate($streamName);

        $this->assertFalse($streamProducer->isAutoIncremented());
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
