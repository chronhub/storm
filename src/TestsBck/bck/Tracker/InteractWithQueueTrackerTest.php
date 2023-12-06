<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Contracts\Tracker\Story;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\Draft;
use Chronhub\Storm\Tracker\InteractWithQueueTracker;
use stdClass;

final class InteractWithQueueTrackerTest extends UnitTestCase
{
    public function testWatchAndDisclose(): void
    {
        $story = new Draft('dispatch');

        $story->withMessage(new Message(new stdClass(), ['init' => 0]));

        $tracker = $this->newInstance();

        $fn = function (Story $story): void {
            $this->incrementMessageHeader($story);
        };

        foreach ([-1000, 0, 100, 10000] as $priority) {
            $tracker->watch('dispatch', $fn, $priority);
        }

        $tracker->disclose($story);

        $this->assertEquals(['init' => 4], $story->message()->headers());

        $story->withEvent('finalize');

        $tracker->watch('finalize', function (Story $story): void {
            $this->incrementMessageHeader($story);
        }, 0);

        $tracker->disclose($story);

        $this->assertEquals(['init' => 5], $story->message()->headers());
    }

    public function testWatchAndStopPropagation(): void
    {
        $story = new Draft('dispatch');

        $story->withMessage(new Message(new stdClass(), ['init' => 0]));

        $tracker = $this->newInstance();

        $tracker->watch('dispatch', function (Story $story): void {
            $this->incrementMessageHeader($story);
        }, -1000);

        $tracker->watch('dispatch', function (Story $story): void {
            $story->stop(true);
        }, -1);

        $tracker->watch('dispatch', function (Story $story): void {
            $this->incrementMessageHeader($story);
        }, 0);

        $tracker->disclose($story);

        $this->assertEquals(['init' => 1], $story->message()->headers());
    }

    public function testWatchAndDiscloseUntil(): void
    {
        $story = new Draft('dispatch');

        $story->withMessage(new Message(new stdClass(), ['init' => 0]));

        $tracker = $this->newInstance();

        $tracker->watch('dispatch', function (Story $story): void {
            $this->incrementMessageHeader($story);
        }, -1000);

        $tracker->watch('dispatch', function (Story $story): void {
            $this->incrementMessageHeader($story);
        }, -1);

        $tracker->watch('dispatch', function (Story $story): void {
            $this->incrementMessageHeader($story);
        }, 0);

        $tracker->discloseUntil($story, function (Story $story): bool {
            return $story->message()->headers()['init'] === 1;
        });

        $this->assertEquals(['init' => 1], $story->message()->headers());
    }

    public function testForgetListener(): void
    {
        $story = new Draft('dispatch');

        $story->withMessage(new Message(new stdClass(), ['init' => 0]));

        $tracker = $this->newInstance();
        $this->assertEmpty($tracker->listeners());

        $listener = $tracker->watch('dispatch', function (Story $story): void {
            //
        }, -1000);

        $this->assertCount(1, $tracker->listeners());

        $tracker->forget($listener);

        $this->assertEmpty($tracker->listeners());
    }

    private function incrementMessageHeader(Story $story): void
    {
        $count = $story->message()->header('init');
        $story->withMessage($story->message()->withHeader('init', $count + 1));
    }

    private function newInstance(): object
    {
        return new class()
        {
            use InteractWithQueueTracker;
        };
    }
}
