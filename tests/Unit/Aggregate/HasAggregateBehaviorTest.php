<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use function iterator_to_array;

final class HasAggregateBehaviorTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId);

        $this->assertEquals(0, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(0, $aggregateRoot->getAppliedEvents());
        $this->assertEquals(0, $aggregateRoot->version());
        $this->assertEquals($aggregateId, $aggregateRoot->aggregateId());
    }

    #[Test]
    public function it_record_events(): void
    {
        $events = iterator_to_array($this->provideThreeDomainEvents());

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId, ...$events);

        $this->assertEquals(3, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(3, $aggregateRoot->version());
        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
    }

    #[Test]
    public function it_release_events(): void
    {
        $events = iterator_to_array($this->provideThreeDomainEvents());

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId, ...$events);

        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
        $this->assertEquals(3, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(3, $aggregateRoot->version());

        $releasedEvents = $aggregateRoot->releaseEvents();

        $this->assertEquals(0, $aggregateRoot->countRecordedEvents());
        $this->assertEquals($events, $releasedEvents);
    }

    #[Test]
    public function it_reconstitute_aggregate_from_events(): void
    {
        $events = $this->provideThreeDomainEvents();

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $events);

        $this->assertInstanceOf(AggregateRoot::class, $aggregateRoot);
        $this->assertInstanceOf(AggregateRootStub::class, $aggregateRoot);

        $this->assertEquals(3, $aggregateRoot->version());
        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
    }

    #[Test]
    public function it_return_null_aggregate_when_reconstitute_with_empty_events(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $this->provideEmptyEvents());

        $this->assertNull($aggregateRoot);
    }

    #[Test]
    public function it_return_null_aggregate_when_reconstitute_with_no_get_return_from_generator(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $this->provideEventsWithNoReturn());

        $this->assertNull($aggregateRoot);
    }

    public function provideThreeDomainEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        return 3;
    }

    public function provideEventsWithNoReturn(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];
    }

    public function provideEmptyEvents(): Generator
    {
        yield from [];

        return 0;
    }
}
