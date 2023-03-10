<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Publisher;

use Chronhub\Storm\Stream\Stream;
use Illuminate\Support\Collection;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Tests\Double\SomeEvent;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Publisher\EventPublisherSubscriber;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use function count;
use function is_countable;

#[CoversClass(EventPublisherSubscriber::class)]
final class EventPublisherSubscriberTest extends UnitTestCase
{
    private EventPublisher|MockObject $eventPublisher;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->eventPublisher = $this->createMock(EventPublisher::class);
    }

    #[Test]
    public function it_publish_events_on_first_commit_with_no_transactional_chronicler(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(Chronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');

        $this->eventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->equalTo(new Collection([$event])));

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_publish_events_on_first_commit_with_eventable_chronicler(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->equalTo(new Collection([$event])));

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_does_not_publish_events_on_first_commit_with_eventable_chronicler_on_stream_already_exists(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);
        $streamStory->withRaisedException(new StreamAlreadyExists('stream already exists'));

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->never())->method('publish');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_publish_events_on_amend_with_eventable_chronicler(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->equalTo(new Collection([$event])));

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_does_not_publish_events_on_amend_with_eventable_chronicler_on_stream_not_found(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);
        $streamStory->withRaisedException(new StreamNotFound('stream not found'));

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->never())->method('publish');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_does_not_publish_events_on_amend_with_eventable_chronicler_on_concurrency(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);
        $streamStory->withRaisedException(new ConcurrencyException('concurrency exception'));

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->never())->method('publish');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_publish_events_on_persist_not_in_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);

        $chronicler->expects($this->once())->method('amend')->with($stream);
        $chronicler->expects($this->once())->method('inTransaction')->willReturn(false);

        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new Collection([$event]);

        $this->eventPublisher->expects($this->never())->method('pull');
        $this->eventPublisher->expects($this->once())->method('publish')->with($pendingEvents);
        $this->eventPublisher->expects($this->never())->method('flush');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_record_events_on_first_commit_in_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $chronicler->expects($this->once())->method('firstCommit')->with($stream);

        $chronicler->expects($this->once())->method('inTransaction')->willReturn(true);
        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new Collection([$event]);

        $this->eventPublisher->expects($this->never())->method('pull');
        $this->eventPublisher->expects($this->never())->method('publish');
        $this->eventPublisher->expects($this->once())->method('record')->with($pendingEvents);

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_record_events_on_persist_in_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $chronicler->expects($this->once())->method('amend')->with($stream);

        $chronicler->expects($this->once())->method('inTransaction')->willReturn(true);

        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new Collection([$event]);

        $this->eventPublisher->expects($this->never())->method('pull');
        $this->eventPublisher->expects($this->never())->method('publish');
        $this->eventPublisher->expects($this->once())->method('record')->with($pendingEvents);

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_publish_events_on_commit_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new LazyCollection([$event]);

        $this->eventPublisher->expects($this->once())->method('pull')->willReturn($pendingEvents);
        $this->eventPublisher->expects($this->once())->method('publish')->with($pendingEvents);
        $this->eventPublisher->expects($this->never())->method('flush');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_flush_pending_events_on_rollback_transaction(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);
        $stream = new Stream(new StreamName('baz'), [$event]);

        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT);
        $streamStory->deferred(fn (): Stream => $stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->never())->method('pull');
        $this->eventPublisher->expects($this->never())->method('publish');

        $this->eventPublisher->expects($this->once())->method('flush');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    #[Test]
    public function it_detach_stream_subscribers(): void
    {
        $streamTracker = new TrackTransactionalStream();
        $this->assertCount(0, $streamTracker->listeners());

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $countProvidedSubscribers = count($streamTracker->listeners());

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $countFromPublisher = is_countable(ReflectionProperty::getProperty($subscriber, 'streamSubscribers'))
            ? count(ReflectionProperty::getProperty($subscriber, 'streamSubscribers'))
            : 0;

        $this->assertEquals(4, $countFromPublisher);

        $subscriber->detachFromChronicler($eventChronicler);

        $this->assertEquals(count($streamTracker->listeners()), $countProvidedSubscribers);
    }
}
