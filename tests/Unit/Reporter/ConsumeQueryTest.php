<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Throwable;
use RuntimeException;
use React\Promise\Deferred;
use Chronhub\Storm\Message\Message;
use React\Promise\PromiseInterface;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Tests\Double\SomeQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\ConsumeQuery;

#[CoversClass(ConsumeQuery::class)]
final class ConsumeQueryTest extends UnitTestCase
{
    #[Test]
    public function it_test_subscriber(): void
    {
        $subscriber = new ConsumeQuery();

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::INVOKE_HANDLER->value
        );
    }

    #[Test]
    public function it_does_not_mark_message_handled_when_query_consumer_is_null(): void
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

    #[Test]
    public function it_consume_query_and_mark_message_handled(): void
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

        $result = $this->handlePromise($story->promise());

        $this->assertEquals(42, $result);
    }

    #[Test]
    public function it_hold_exception_on_promise_when_query_is_handled_and_mark_message_handled(): void
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

        $result = $this->handlePromise($story->promise());

        $this->assertInstanceOf(RuntimeException::class, $result);
        $this->assertEquals('some exception', $result->getMessage());
    }

    private function handlePromise(PromiseInterface $promise): mixed
    {
        $exception = null;
        $result = null;

        $promise->then(
            static function ($data) use (&$result): void {
                $result = $data;
            },
            static function ($exc) use (&$exception): void {
                $exception = $exc;
            }
        );

        if ($exception instanceof Throwable) {
            return $exception;
        }

        return $result;
    }
}
