<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Contracts\Message\Header;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Reporter\Subscribers\ConsumeCommand;

#[CoversClass(ConsumeCommand::class)]
final class ConsumeCommandTest extends UnitTestCase
{
    #[Test]
    public function it_test_subscriber(): void
    {
        $subscriber = new ConsumeCommand();

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::INVOKE_HANDLER->value
        );
    }

    #[Test]
    public function it_consume_command_when_consumers_is_not_empty(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeCommand();
        $subscriber->attachToReporter($tracker);

        $command = SomeCommand::fromContent(['say_my_name' => '?']);

        $response = null;

        $handler = function (DomainCommand $command) use (&$response): void {
            $this->assertInstanceOf(SomeCommand::class, $command);
            $this->assertEquals(['say_my_name' => '?'], $command->toContent());

            $response = 'Heisenberg';
        };

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $this->assertNull($story->consumers()->current());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($command));
        $story->withConsumers([$handler]);

        $tracker->disclose($story);

        $this->assertEquals('Heisenberg', $response);
        $this->assertTrue($story->isHandled());
    }

    #[Test]
    public function it_mark_message_handled_with_no_consumer_and_truthy_event_dispatched_header(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeCommand();
        $subscriber->attachToReporter($tracker);

        $command = SomeCommand::fromContent([]);
        $message = new Message($command, [Header::EVENT_DISPATCHED => true]);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertFalse($story->isHandled());

        $story->withMessage($message);

        $tracker->disclose($story);

        $this->assertNull($story->consumers()->current());

        $this->assertTrue($story->isHandled());
    }

    #[Test]
    public function it_does_not_mark_message_handled_with_no_consumer_and_falsy_event_dispatched_header(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeCommand();
        $subscriber->attachToReporter($tracker);

        $command = SomeCommand::fromContent([]);
        $message = new Message($command, [Header::EVENT_DISPATCHED => false]);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertFalse($story->isHandled());

        $story->withMessage($message);

        $tracker->disclose($story);

        $this->assertNull($story->consumers()->current());

        $this->assertFalse($story->isHandled());
    }
}
