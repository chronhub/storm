<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Publisher;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Illuminate\Support\LazyCollection;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\ReportEvent;
use Chronhub\Storm\Publisher\PublishEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use function iterator_count;

// todo iterable test as contract
final class PublishEventTest extends ProphecyTestCase
{
    private ReportEvent|ObjectProphecy $reporter;

    public function setUp(): void
    {
        parent::setUp();

        $this->reporter = $this->prophesize(ReportEvent::class);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $publisher = new PublishEvent($this->reporter->reveal());

        $this->assertCountPendingEvents(0, $publisher);
    }

    /**
     * @test
     */
    public function it_record_events(): void
    {
        $this->reporter->relay(Argument::type(DomainEvent::class))->shouldNotBeCalled();

        $publisher = new PublishEvent($this->reporter->reveal());

        $this->assertCountPendingEvents(0, $publisher);

        $i = 0;
        while ($i !== 10) {
            $publisher->record($this->provideEvents());

            $i++;

            $this->assertCountPendingEvents($i, $publisher);
        }
    }

    /**
     * @test
     */
    public function it_record_many_events(): void
    {
        $this->reporter->relay(Argument::type(DomainEvent::class))->shouldNotBeCalled();

        $publisher = new PublishEvent($this->reporter->reveal());

        $this->assertCountPendingEvents(0, $publisher);

        $events = new LazyCollection([
            new SomeEvent(['name' => 'steph']),
            new SomeEvent(['name' => 'steph bug']),
        ]);

        $publisher->record($events);
        $this->assertCountPendingEvents(2, $publisher);

        $publisher->record($events);
        $this->assertCountPendingEvents(4, $publisher);
    }

    /**
     * @test
     */
    public function it_record_events_in_order_they_arrived(): void
    {
        $this->reporter->relay(Argument::type(DomainEvent::class))->shouldNotBeCalled();

        $publisher = new PublishEvent($this->reporter->reveal());

        $this->assertCountPendingEvents(0, $publisher);

        $i = 1;
        while ($i !== 5) {
            $event = [(new SomeEvent(['name' => 'steph bug']))->withHeader('no', $i)];

            $publisher->record(new LazyCollection($event));

            $i++;
        }

        $pendingEvents = $publisher->pull()->getIterator();

        $out = 1;
        foreach ($pendingEvents as $event) {
            $this->assertEquals($out, $event->header('no'));
            $out++;
        }
    }

    /**
     * @test
     */
    public function it_pull_and_flush_pending_events(): void
    {
        $this->reporter->relay(Argument::type(DomainEvent::class))->shouldNotBeCalled();

        $publisher = new PublishEvent($this->reporter->reveal());

        $this->assertCountPendingEvents(0, $publisher);

        $i = 0;
        while ($i !== 5) {
            $publisher->record($this->provideEvents());

            $i++;

            $this->assertCountPendingEvents($i, $publisher);
        }

        $pendingEvents = $publisher->pull();

        $this->assertEquals(5, iterator_count($pendingEvents));

        $this->assertCountPendingEvents(0, $publisher);
    }

    /**
     * @test
     */
    public function it_publish_events(): void
    {
        $this->reporter->relay(Argument::type(SomeEvent::class))->shouldBeCalledTimes(10);

        $publisher = new PublishEvent($this->reporter->reveal());

        $this->assertCountPendingEvents(0, $publisher);

        $i = 0;
        while ($i !== 10) {
            $publisher->record($this->provideEvents());

            $i++;

            $this->assertCountPendingEvents($i, $publisher);
        }

        $publisher->publish($publisher->pull());
    }

    /**
     * @test
     */
    public function it_flush_pending_events(): void
    {
        $this->reporter->relay(Argument::type(DomainEvent::class))->shouldNotBeCalled();

        $publisher = new PublishEvent($this->reporter->reveal());

        $this->assertCountPendingEvents(0, $publisher);

        $i = 0;
        while ($i !== 4) {
            $publisher->record($this->provideEvents());

            $i++;

            $this->assertCountPendingEvents($i, $publisher);
        }

        $publisher->flush();

        $this->assertCountPendingEvents(0, $publisher);
    }

    private function provideEvents(): LazyCollection
    {
        return new LazyCollection([new SomeEvent(['name' => 'steph bug'])]);
    }

    private function assertCountPendingEvents(int $expectedCount, EventPublisher $publisher): void
    {
        $pendingEvents = ReflectionProperty::getProperty($publisher, 'pendingEvents');

        $this->assertEquals($expectedCount, $pendingEvents->count());
    }
}
