<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Chronicler\EventDraft;
use Chronhub\Storm\Chronicler\TrackStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TrackStream::class)]
final class TrackStreamTest extends UnitTestCase
{
    #[DataProvider('provideEventName')]
    #[Test]
    public function it_create_new_story(?string $eventName = null): void
    {
        $tracker = new TrackStream();

        $draft = $tracker->newStory($eventName);

        $this->assertInstanceOf(EventDraft::class, $draft);
        $this->assertEquals($eventName, $draft->currentEvent());
        $this->assertTrue($tracker->listeners()->isEmpty());
    }

    public static function provideEventName(): Generator
    {
        yield ['dispatch'];
        yield ['finalize'];
    }
}
