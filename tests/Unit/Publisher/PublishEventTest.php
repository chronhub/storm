<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Publisher;

use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use Chronhub\Storm\Publisher\PublishEvent;
use Chronhub\Storm\Reporter\ReportEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use function iterator_count;

#[CoversClass(PublishEvent::class)]
final class PublishEventTest extends UnitTestCase
{
    private ReportEvent|MockObject $reporter;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->reporter = $this->createMock(ReportEvent::class);
    }

    public function testInstance(): void
    {
        $publisher = new PublishEvent($this->reporter);

        $this->assertCountPendingEvents(0, $publisher);
    }

    public function testRecordEvents(): void
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

    public function testRecordManyEvents(): void
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

    public function testRecordEventsFirstIn(): void
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

    public function testPullAndFlushPendingEvents(): void
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

    public function testPublishEvents(): void
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

    public function testFlushPendingEvents(): void
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
