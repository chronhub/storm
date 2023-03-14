<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use stdClass;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Message\DecorateMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Message\MessageDecorator;

#[CoversClass(DecorateMessage::class)]
final class DecorateMessageTest extends UnitTestCase
{
    #[Test]
    public function it_decorate_message_by_subscribing_to_tracker(): void
    {
        $messageDecorator = new class() implements MessageDecorator
        {
            public function decorate(Message $message): Message
            {
                return $message->withHeader('foo', 'bar');
            }
        };

        $messageSubscriber = new DecorateMessage($messageDecorator);

        $message = new Message(new stdClass());

        $tracker = new TrackMessage();

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $messageSubscriber->attachToReporter($tracker);

        $tracker->disclose($story);

        $this->assertEquals(['foo' => 'bar'], $story->message()->headers());
    }

    #[Test]
    public function it_can_be_untracked(): never
    {
        $this->markTestSkipped('assert message listener must be move to support testing in message pkg');
    }
}
