<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\HasAggregateBehaviour;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;

use function iterator_to_array;

#[CoversClass(HasAggregateBehaviour::class)]
final class HasAggregateBehaviorTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId);

        $this->assertEquals(0, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(0, $aggregateRoot->getAppliedEvents());
        $this->assertEquals(0, $aggregateRoot->version());
        $this->assertEquals($aggregateId, $aggregateRoot->aggregateId());
    }

    public function testRecordEvents(): void
    {
        $events = iterator_to_array($this->provideThreeDomainEvents());

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId, ...$events);

        $this->assertEquals(3, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(3, $aggregateRoot->version());
        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
    }

    public function testReleaseEvents(): void
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

    public function testReconstituteAggregateFromHistoryOfEvents(): void
    {
        $events = $this->provideThreeDomainEvents();

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $events);

        $this->assertInstanceOf(AggregateRoot::class, $aggregateRoot);
        $this->assertInstanceOf(AggregateRootStub::class, $aggregateRoot);

        $this->assertEquals(3, $aggregateRoot->version());
        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
    }

    public function testReturnNullWithEmptyHistory(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $this->provideEmptyEvents());

        $this->assertNull($aggregateRoot);
    }

    public function testReturnNullWithNoGetReturnFromGenerator(): void
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
