<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Stream\SingleStreamPerAggregate;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final class SingleStreamPerAggregateTest extends ProphecyTestCase
{
    private AggregateIdentity $aggregateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregateId = V4AggregateId::create();
    }

    /**
     * @test
     */
    public function it_determine_stream_name_without_aggregate_identity(): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertEquals(
            new StreamName('some_stream_name'),
            $streamProducer->toStreamName($this->aggregateId)
        );
    }

    /**
     * @test
     *
     * @dataProvider provideEvents
     */
    public function it_produce_stream(iterable $events): void
    {
        $streamName = new StreamName('some_stream_name');
        $aggregateId = $this->prophesize(AggregateIdentity::class);
        $aggregateId->toString()->shouldNotBeCalled();

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $stream = new Stream(new StreamName('some_stream_name'), $events);

        $this->assertNotSame($streamName, $stream->name());
        $this->assertEquals($stream, $streamProducer->toStream($aggregateId->reveal(), $events));
    }

    /**
     * @test
     *
     * @dataProvider provideEventsForFirstCommit
     */
    public function it_always_return_false_to_determine_if_event_is_first_commit(DomainEvent $event): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertFalse($streamProducer->isFirstCommit($event));
    }

    /**
     * @test
     */
    public function it_check_if_is_single_stream_per_aggregate_strategy(): void
    {
        $streamName = new StreamName('some_stream_name');

        $streamProducer = new SingleStreamPerAggregate($streamName);

        $this->assertTrue($streamProducer->isAutoIncremented());
    }

    public function provideEvents(): Generator
    {
        yield [[]];
        yield [[SomeEvent::fromContent(['steph' => 'bug'])]];
    }

    public function provideEventsForFirstCommit(): Generator
    {
        yield [
            SomeEvent::fromContent(['steph' => 'bug'])
                ->withHeader(EventHeader::AGGREGATE_VERSION, 1),
        ];

        yield [
            SomeEvent::fromContent(['steph' => 'bug'])
                ->withHeader(EventHeader::AGGREGATE_VERSION, 2),
        ];

        yield [
            SomeEvent::fromContent(['steph' => 'bug'])
                ->withHeader(EventHeader::AGGREGATE_VERSION, 20),
        ];
    }
}
