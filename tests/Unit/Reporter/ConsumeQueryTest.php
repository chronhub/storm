<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\ConsumeQuery;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;

#[CoversClass(ConsumeQuery::class)]
final class ConsumeQueryTest extends UnitTestCase
{
    private PromiseHandlerStub $promiseHandler;

    protected function setUp(): void
    {
        $this->promiseHandler = new PromiseHandlerStub();
    }

    public function testSubscriber(): void
    {
        $subscriber = new ConsumeQuery();

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::INVOKE_HANDLER->value
        );
    }

    public function testDoesNotMarkMessageHandledWithNullConsumer(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeQuery();
        $subscriber->attachToReporter($tracker);

        $query = SomeQuery::fromContent([]);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertNull($story->consumers()->current());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($query));
        $story->withConsumers([]);

        $tracker->disclose($story);

        $this->assertFalse($story->isHandled());
    }

    public function testConsumerQueryAndMarkMessageHandled(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeQuery();
        $subscriber->attachToReporter($tracker);

        $query = SomeQuery::fromContent(['return' => 42]);

        $consumer = static function (SomeQuery $query, Deferred $promise): void {
            $promise->resolve($query->content['return']);
        };

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertNull($story->consumers()->current());
        $this->assertNull($story->promise());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($query));
        $story->withConsumers([$consumer]);

        $tracker->disclose($story);

        $this->assertTrue($story->isHandled());
        $this->assertInstanceOf(PromiseInterface::class, $story->promise());

        $result = $this->promiseHandler->handlePromise($story->promise(), true);

        $this->assertEquals(42, $result);
    }

    public function testPromiseHoldExceptionAndMarkMessageHandled(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeQuery();
        $subscriber->attachToReporter($tracker);

        $query = SomeQuery::fromContent([]);

        $consumer = static function (): never {
            throw new RuntimeException('some exception');
        };

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertNull($story->consumers()->current());
        $this->assertNull($story->promise());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($query));
        $story->withConsumers([$consumer]);

        $tracker->disclose($story);

        $this->assertTrue($story->isHandled());

        $this->assertInstanceOf(PromiseInterface::class, $story->promise());

        $result = $this->promiseHandler->handlePromise($story->promise(), false);

        $this->assertInstanceOf(RuntimeException::class, $result);
        $this->assertEquals('some exception', $result->getMessage());
    }
}
