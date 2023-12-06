<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Publisher;

use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use Chronhub\Storm\Contracts\Reporter\EventReporter;
use Chronhub\Storm\Publisher\PublishEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

use function iterator_count;

#[CoversClass(PublishEvent::class)]
final class PublishEventTest extends UnitTestCase
{
    private EventReporter|MockObject $reporter;

    public function setUp(): void
    {
        $this->reporter = $this->createMock(EventReporter::class);
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

        $this->assertRecordEvents($publisher, 10);
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

        $this->assertRecordEvents($publisher, 5);

        $pendingEvents = $publisher->pull();

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

        $this->assertRecordEvents($publisher, 5);

        $pendingEvents = $publisher->pull();

        $this->assertEquals(5, iterator_count($pendingEvents));

        $this->assertCountPendingEvents(0, $publisher);
    }

    public function testPublishEvents(): void
    {
        $this->reporter->expects($this->exactly(10))->method('relay');

        $publisher = new PublishEvent($this->reporter);

        $this->assertCountPendingEvents(0, $publisher);

        $this->assertRecordEvents($publisher, 10);

        $publisher->publish($publisher->pull());
    }

    public function testFlushPendingEvents(): void
    {
        $this->reporter->expects($this->never())->method('relay');

        $publisher = new PublishEvent($this->reporter);

        $this->assertCountPendingEvents(0, $publisher);

        $this->assertRecordEvents($publisher, 4);

        $publisher->flush();

        $this->assertCountPendingEvents(0, $publisher);
    }

    private function assertRecordEvents(PublishEvent $publisher, int $count): void
    {
        $i = 1;
        while ($i !== $count + 1) {
            $publisher->record($this->provideEvents($i));

            $this->assertCountPendingEvents($i, $publisher);

            $i++;
        }
    }

    private function assertCountPendingEvents(int $expectedCount, EventPublisher $publisher): void
    {
        $pendingEvents = ReflectionProperty::getProperty($publisher, 'pendingEvents');

        $this->assertEquals($expectedCount, $pendingEvents->count());
    }

    private function provideEvents(int $sequence): LazyCollection
    {
        $events = (new SomeEvent(['name' => 'steph']))->withHeader('no', $sequence);

        return new LazyCollection([$events]);
    }
}
