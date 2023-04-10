<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\Draft;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TrackMessage::class)]
final class TrackMessageTest extends UnitTestCase
{
    #[DataProvider('provideEvent')]
    public function testNewStory(string $event): void
    {
        $tracker = new TrackMessage();

        $story = $tracker->newStory($event);

        $this->assertInstanceOf(Draft::class, $story);

        $this->assertEquals($event, $story->currentEvent());
    }

    public function testOnDispatch(): void
    {
        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $callback = function (MessageStory $story): void {
            $this->assertEquals(Reporter::DISPATCH_EVENT, $story->currentEvent());
        };

        $listener = $tracker->onDispatch($callback, 10);
        $tracker->disclose($story);

        $this->assertSame(Reporter::DISPATCH_EVENT, $listener->name());
        $this->assertSame($callback, $listener->story());
        $this->assertSame(10, $listener->priority());
    }

    public function testOnFinalize(): void
    {
        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::FINALIZE_EVENT);

        $callback = function (MessageStory $story): void {
            $this->assertEquals(Reporter::FINALIZE_EVENT, $story->currentEvent());
        };

        $listener = $tracker->onFinalize($callback, 100);
        $tracker->disclose($story);

        $this->assertSame(Reporter::FINALIZE_EVENT, $listener->name());
        $this->assertSame($callback, $listener->story());
        $this->assertSame(100, $listener->priority());
    }

    public static function provideEvent(): Generator
    {
        yield ['dispatch'];
        yield ['finalize'];
    }
}
