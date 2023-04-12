<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Tests\Stubs\StoryStub;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\InteractWithStory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InteractWithStory::class)]
class InteractWithStoryTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $story = new StoryStub(null);

        $this->assertNull($story->getCurrentEvent());
        $this->assertFalse($story->isStopped());
        $this->assertFalse($story->hasException());
        $this->assertNull($story->exception());
    }

    public function testInstanceWithEvent(): void
    {
        $story = new StoryStub('foo');

        $this->assertSame('foo', $story->getCurrentEvent());
    }

    public function testOverrideEvent(): void
    {
        $story = new StoryStub('foo');

        $this->assertSame('foo', $story->currentEvent());

        $story->withEvent('bar');

        $this->assertSame('bar', $story->currentEvent());
    }

    public function testException(): void
    {
        $story = new StoryStub('foo');

        $this->assertFalse($story->hasException());

        $exception = new Exception('foo');

        $story->withRaisedException($exception);

        $this->assertEquals($exception, $story->exception());
    }

    public function testResetException(): void
    {
        $story = new StoryStub('foo');

        $this->assertFalse($story->hasException());

        $exception = new Exception('foo');

        $story->withRaisedException($exception);

        $this->assertEquals($exception, $story->exception());

        $story->resetException();

        $this->assertFalse($story->hasException());
        $this->assertNull($story->exception());
    }

    public function testStopPropagationOfEvent(): void
    {
        $story = new StoryStub('foo');

        $this->assertFalse($story->isStopped());

        $story->stop(true);

        $this->assertTrue($story->isStopped());
    }
}
