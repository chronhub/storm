<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Tracker\Draft;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TrackMessage::class)]
final class TrackMessageTest extends UnitTestCase
{
    #[Test]
    public function it_start_new_story(): void
    {
        $tracker = new TrackMessage();

        $story = $tracker->newStory('dispatch');

        $this->assertInstanceOf(Draft::class, $story);

        $this->assertEquals('dispatch', $story->currentEvent());
    }
}
