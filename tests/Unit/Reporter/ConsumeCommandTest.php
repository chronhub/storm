<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\ConsumeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ConsumeCommand::class)]
final class ConsumeCommandTest extends UnitTestCase
{
    public function testSubscriber(): void
    {
        $subscriber = new ConsumeCommand();

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::INVOKE_HANDLER->value
        );
    }

    public function testConsumeCommand(): void
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

    public function testMarkMessageHandled(): void
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

    public function testDoesNotMarkMessageHandled(): void
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
