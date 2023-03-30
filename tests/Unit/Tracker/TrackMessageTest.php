<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Generator;
use Chronhub\Storm\Tracker\Draft;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
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

    public static function provideEvent(): Generator
    {
        yield ['dispatch'];
        yield ['finalize'];
    }
}
