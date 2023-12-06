<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\EventDraft;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\InteractWithStory;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TrackStream::class)]
#[CoversClass(InteractWithStory::class)]
final class TrackStreamTest extends UnitTestCase
{
    #[DataProvider('provideEventName')]
    public function testNewStoryInstance(string $eventName = null): void
    {
        $tracker = new TrackStream();

        $draft = $tracker->newStory($eventName);

        $this->assertInstanceOf(EventDraft::class, $draft);
        $this->assertEquals($eventName, $draft->currentEvent());
        $this->assertTrue($tracker->listeners()->isEmpty());
    }

    public function testStopPropagationOfStory(): void
    {
        $tracker = new TrackStream();

        $draft = $tracker->newStory('foo');

        $listeners = [];

        $listeners[] = $tracker->watch('foo', function (StreamStory $story) {
            $story->deferred(fn () => 1);
        });

        $listeners[] = $tracker->watch('foo', function (StreamStory $story) {
            $story->deferred(fn () => 2);
            $story->stop(true);
        });

        $listeners[] = $tracker->watch('foo', function (StreamStory $story) {
            $story->deferred(fn () => 3);
        });

        $this->assertEquals($listeners, $tracker->listeners()->toArray());

        $tracker->disclose($draft);

        $this->assertEquals(2, $draft->promise());
    }

    public static function provideEventName(): Generator
    {
        yield ['dispatch'];
        yield ['finalize'];
    }
}
