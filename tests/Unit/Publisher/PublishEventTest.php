<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Publisher;

use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Support\LazyCollection;
use Chronhub\Storm\Reporter\ReportEvent;
use Chronhub\Storm\Publisher\PublishEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use function iterator_count;

final class PublishEventTest extends UnitTestCase
{
    private ReportEvent|MockObject $reporter;

    public function setUp(): void
    {
        parent::setUp();

        $this->reporter = $this->createMock(ReportEvent::class);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $publisher = new PublishEvent($this->reporter);

        $this->assertCountPendingEvents(0, $publisher);
    }

    /**
     * @test
     */
    public function it_record_events(): void
    {
        $this->reporter->expects($this->never())->method('relay');

        $publisher = new PublishEvent($this->reporter);

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
        $this->reporter->expects($this->never())->method('relay');

        $publisher = new PublishEvent($this->reporter);

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
        $this->reporter->expects($this->never())->method('relay');

        $publisher = new PublishEvent($this->reporter);

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
        $this->reporter->expects($this->never())->method('relay');

        $publisher = new PublishEvent($this->reporter);

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
        $this->reporter->expects($this->exactly(10))->method('relay');

        $publisher = new PublishEvent($this->reporter);

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
        $this->reporter->expects($this->never())->method('relay');

        $publisher = new PublishEvent($this->reporter);

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
