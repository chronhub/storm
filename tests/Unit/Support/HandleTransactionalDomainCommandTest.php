<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Support\Bridge\HandleTransactionalDomainCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class HandleTransactionalDomainCommandTest extends UnitTestCase
{
    private Message $message;

    private TransactionalEventableChronicler|MockObject $chronicler;

    public function setup(): void
    {
        $this->message = new Message(SomeCommand::fromContent(['name' => 'steph bug']));
        $this->chronicler = $this->createMock(TransactionalEventableChronicler::class);
    }

    public function testBeginTransaction(): void
    {
        $this->chronicler->expects($this->once())->method('beginTransaction');

        $subscriber = new HandleTransactionalDomainCommand($this->chronicler);

        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($this->message);

        $subscriber->attachToReporter($tracker);

        $tracker->disclose($story);
    }

    public function testCommitTransaction(): void
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
    public function testNoCommitMadeWhenNotInTransaction(): void
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

    public function testRollbackTransaction(): void
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

    public function testDoesNotAffectTransaction(): void
    {
        $mock = $this->createMock(Chronicler::class);

        $this->chronicler->expects($this->never())->method('inTransaction');
        $this->chronicler->expects($this->never())->method('beginTransaction');
        $this->chronicler->expects($this->never())->method('rollbackTransaction');
        $this->chronicler->expects($this->never())->method('commitTransaction');

        $subscriber = new HandleTransactionalDomainCommand($mock);

        $tracker = new TrackMessage();
        $story = $tracker->newStory(Reporter::FINALIZE_EVENT);
        $story->withMessage($this->message);

        $this->assertFalse($story->hasException());

        $subscriber->attachToReporter($tracker);

        $tracker->disclose($story);
    }
}
