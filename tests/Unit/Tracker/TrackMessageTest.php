<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Tracker\Draft;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;

final class TrackMessageTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_start_new_story(): void
    {
        $tracker = new TrackMessage();

        $story = $tracker->newStory('dispatch');

        $this->assertInstanceOf(Draft::class, $story);

        $this->assertEquals('dispatch', $story->currentEvent());
    }
}
