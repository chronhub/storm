<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use RuntimeException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Support\Bridge\HandleTransactionalDomainCommand;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;

class HandleTransactionalDomainCommandTest extends UnitTestCase
{
    private Message $message;

    private TransactionalEventableChronicler|MockObject $chronicler;

    public function setup(): void
    {
        $this->message = new Message(SomeCommand::fromContent(['name' => 'steph bug']));
        $this->chronicler = $this->createMock(TransactionalEventableChronicler::class);
    }

    #[Test]
    public function it_begin_transaction_on_dispatch_command(): void
    {
        $this->chronicler->expects($this->once())->method('beginTransaction');

        $subscriber = new HandleTransactionalDomainCommand($this->chronicler);

        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($this->message);

        $subscriber->attachToReporter($tracker);

        $tracker->disclose($story);
    }

    #[Test]
    public function it_commit_transaction_on_finalize_when_no_exception_found_in_context_and_chronicler_in_transaction(): void
    {
        $this->chronicler->expects($this->once())->method('inTransaction')->willReturn(true);
        $this->chronicler->expects($this->once())->method('commitTransaction');

        $subscriber = new HandleTransactionalDomainCommand($this->chronicler);

        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::FINALIZE_EVENT);
        $story->withMessage($this->message);

        $this->assertFalse($story->hasException());

        $subscriber->attachToReporter($tracker);

        $tracker->disclose($story);
    }

    #[Test]
    public function it_does_not_commit_transaction_when_chronicler_not_in_transaction(): void
    {
        $this->chronicler->expects($this->once())->method('inTransaction')->willReturn(false);
        $this->chronicler->expects($this->never())->method('commitTransaction');

        $subscriber = new HandleTransactionalDomainCommand($this->chronicler);

        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::FINALIZE_EVENT);
        $story->withMessage($this->message);

        $this->assertFalse($story->hasException());

        $subscriber->attachToReporter($tracker);

        $tracker->disclose($story);
    }

    #[Test]
    public function it_rollback_transaction_when_context_has_exception(): void
    {
        $this->chronicler->expects($this->once())->method('inTransaction')->willReturn(true);
        $this->chronicler->expects($this->once())->method('rollbackTransaction');
        $this->chronicler->expects($this->never())->method('commitTransaction');

        $subscriber = new HandleTransactionalDomainCommand($this->chronicler);

        $tracker = new TrackMessage();
        $context = $tracker->newStory(Reporter::FINALIZE_EVENT);
        $context->withMessage($this->message);
        $context->withRaisedException(new RuntimeException('failed'));

        $subscriber->attachToReporter($tracker);

        $tracker->disclose($context);
    }

    #[Test]
    public function it_does_not_commit_transaction_if_chronicler_is_not_transactional_and_eventable(): void
    {
        $mock = $this->createMock(Chronicler::class);

        $subscriber = new HandleTransactionalDomainCommand($mock);

        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::FINALIZE_EVENT);
        $story->withMessage($this->message);

        $this->assertFalse($story->hasException());

        $subscriber->attachToReporter($tracker);

        $tracker->disclose($story);
    }
}
