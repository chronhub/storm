<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\DecorateMessage;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(DecorateMessage::class)]
final class DecorateMessageTest extends UnitTestCase
{
    private TrackMessage $tracker;

    protected function setUp(): void
    {
        $this->tracker = new TrackMessage();
        $this->assertEmpty($this->tracker->listeners());
    }

    public function testDecorateMessageBySubscribingToTracker(): void
    {
        $messageDecorator = $this->provideMessageDecorator();
        $messageSubscriber = new DecorateMessage($messageDecorator);
        $message = new Message(new stdClass());

        $this->assertTrue($this->tracker->listeners()->isEmpty());

        $story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $messageSubscriber->attachToReporter($this->tracker);

        $this->tracker->disclose($story);

        $this->assertEquals(['foo' => 'bar'], $story->message()->headers());
    }

    public function testUntrackedListeners(): void
    {
        $messageDecorator = $this->provideMessageDecorator();
        $messageSubscriber = new DecorateMessage($messageDecorator);
        $message = new Message(new stdClass());

        $this->assertTrue($this->tracker->listeners()->isEmpty());

        $story = $this->tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $messageSubscriber->attachToReporter($this->tracker);

        $this->assertCount(1, $this->tracker->listeners());

        $messageSubscriber->detachFromReporter($this->tracker);

        $this->assertCount(0, $this->tracker->listeners());

        $this->tracker->disclose($story);

        $this->assertEmpty($story->message()->headers());
    }

    private function provideMessageDecorator(): MessageDecorator
    {
        return new class() implements MessageDecorator
        {
            public function decorate(Message $message): Message
            {
                return $message->withHeader('foo', 'bar');
            }
        };
    }
}
