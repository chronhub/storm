<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Tracker;

use Chronhub\Storm\Tests\Stubs\StoryStub;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\InteractWithStory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InteractWithStory::class)]
class InteractWithStoryTest extends UnitTestCase
{
    #[Test]
    public function it_assert_instance(): void
    {
        $story = new StoryStub(null);

        $this->assertNull($story->getCurrentEvent());
        $this->assertFalse($story->isStopped());
        $this->assertFalse($story->hasException());
        $this->assertNull($story->exception());
    }

    #[Test]
    public function it_assert_instance_with_event(): void
    {
        $story = new StoryStub('foo');

        $this->assertSame('foo', $story->getCurrentEvent());
    }

    #[Test]
    public function it_override_event(): void
    {
        $story = new StoryStub('foo');

        $this->assertSame('foo', $story->currentEvent());

        $story->withEvent('bar');

        $this->assertSame('bar', $story->currentEvent());
    }

    #[Test]
    public function it_set_exception(): void
    {
        $story = new StoryStub('foo');

        $this->assertFalse($story->hasException());

        $exception = new Exception('foo');

        $story->withRaisedException($exception);

        $this->assertEquals($exception, $story->exception());
    }

    #[Test]
    public function it_reset_exception(): void
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

    #[Test]
    public function it_stop_event(): void
    {
        $story = new StoryStub('foo');

        $this->assertFalse($story->isStopped());

        $story->stop(true);

        $this->assertTrue($story->isStopped());
    }
}
