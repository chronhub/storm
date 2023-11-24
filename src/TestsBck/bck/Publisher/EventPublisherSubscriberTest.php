<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Publisher;

use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventPublisher;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Publisher\EventPublisherSubscriber;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use function count;
use function is_countable;

#[CoversClass(EventPublisherSubscriber::class)]
final class EventPublisherSubscriberTest extends UnitTestCase
{
    private EventPublisher|MockObject $eventPublisher;

    private SomeEvent $event;

    private Stream $stream;

    protected function setUp(): void
    {
        $this->eventPublisher = $this->createMock(EventPublisher::class);
        $this->event = SomeEvent::fromContent(['foo' => 'bar']);
        $this->stream = new Stream(new StreamName('baz'), [$this->event]);
    }

    public function testMarshallEventsWithStandaloneChroniclerOnFirstCommit(): void
    {
        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

        $chronicler = $this->createMock(Chronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');

        $this->eventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->equalTo(new Collection([$this->event])));

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testMarshallEventsWithEventableChroniclerOnFirstCommit(): void
    {
        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');

        $this->eventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->equalTo(new Collection([$this->event])));

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testDoesNotPublishEventsWithEventableChroniclerOnFirstCommitOnStreamAlreadyExistsException(): void
    {
        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);
        $streamStory->withRaisedException(new StreamAlreadyExists('stream already exists'));

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->never())->method('publish');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testPublishEventsOnPersistEvent(): void
    {
        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->equalTo(new Collection([$this->event])));

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testDoesNotPublishOnStreamNotFoundExceptionWhenDispatchPersistEvent(): void
    {
        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);
        $streamStory->withRaisedException(new StreamNotFound('stream not found'));

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->never())->method('publish');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testDoesNotPublishOnConcurrencyExceptionWhenDispatchPersistEvent(): void
    {
        $streamTracker = new TrackStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);
        $streamStory->withRaisedException(new ConcurrencyException('concurrency exception'));

        $chronicler = $this->createMock(EventableChronicler::class);
        $eventChronicler = new EventChronicler($chronicler, $streamTracker);

        $this->eventPublisher->expects($this->never())->method('record');
        $this->eventPublisher->expects($this->never())->method('publish');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testPublishEventsNotInTransactionWhenDispatchPersistEvent(): void
    {
        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);

        $chronicler->expects($this->once())->method('amend')->with($this->stream);
        $chronicler->expects($this->once())->method('inTransaction')->willReturn(false);

        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new Collection([$this->event]);

        $this->eventPublisher->expects($this->never())->method('pull');
        $this->eventPublisher->expects($this->once())->method('publish')->with($pendingEvents);
        $this->eventPublisher->expects($this->never())->method('flush');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testRecordEventsInTransactionWhenDispatchFirstCommitEvent(): void
    {
        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::FIRST_COMMIT_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $chronicler->expects($this->once())->method('firstCommit')->with($this->stream);

        $chronicler->expects($this->once())->method('inTransaction')->willReturn(true);
        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new Collection([$this->event]);

        $this->eventPublisher->expects($this->never())->method('pull');
        $this->eventPublisher->expects($this->never())->method('publish');
        $this->eventPublisher->expects($this->once())->method('record')->with($pendingEvents);

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testRecordEventsInTransactionWhenDispatchPersistEvent(): void
    {
        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(EventableChronicler::PERSIST_STREAM_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $chronicler->expects($this->once())->method('amend')->with($this->stream);
        $chronicler->expects($this->once())->method('inTransaction')->willReturn(true);

        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new Collection([$this->event]);

        $this->eventPublisher->expects($this->never())->method('pull');
        $this->eventPublisher->expects($this->never())->method('publish');
        $this->eventPublisher->expects($this->once())->method('record')->with($pendingEvents);

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testPublishEventWhenDispatchCommitTransactionEvent(): void
    {
        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

        $chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $eventChronicler = new TransactionalEventChronicler($chronicler, $streamTracker);

        $pendingEvents = new LazyCollection([$this->event]);

        $this->eventPublisher->expects($this->once())->method('pull')->willReturn($pendingEvents);
        $this->eventPublisher->expects($this->once())->method('publish')->with($pendingEvents);
        $this->eventPublisher->expects($this->never())->method('flush');

        $subscriber = new EventPublisherSubscriber($this->eventPublisher);
        $subscriber->attachToChronicler($eventChronicler);

        $streamTracker->disclose($streamStory);
    }

    public function testFlushPendingEventsWhenDispatchRollbackTransactionEvent(): void
    {
        $streamTracker = new TrackTransactionalStream();
        $streamStory = $streamTracker->newStory(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT);
        $streamStory->deferred(fn (): Stream => $this->stream);

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

    public function testDetachStreamSubscribers(): void
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
