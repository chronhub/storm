<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support\Auth;

use Chronhub\Storm\Contracts\Auth\AuthorizeMessage;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Support\Auth\GuardCommand;
use Chronhub\Storm\Support\Auth\UnauthorizedException;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\MockObject\MockObject;

class GuardCommandTest extends UnitTestCase
{
    private MessageAlias|MockObject $messageAlias;

    private AuthorizeMessage|MockObject $authorization;

    protected function setUp(): void
    {
        $this->messageAlias = $this->createMock(MessageAlias::class);
        $this->authorization = $this->createMock(AuthorizeMessage::class);
    }

    public function testGrantCommand(): void
    {
        $tracker = new TrackMessage();

        $messageName = SomeCommand::class;
        $message = new Message(new SomeCommand(['foo' => 'bar']), [Header::EVENT_TYPE => $messageName]);

        $this->messageAlias
            ->expects($this->once())
            ->method('classToAlias')
            ->with($messageName)
            ->willReturn($messageName);

        $this->authorization
            ->expects($this->once())
            ->method('isNotGranted')
            ->with($messageName, $message, GuardCommand::class)
            ->willReturn(false);

        $guard = new GuardCommand($this->authorization, $this->messageAlias);
        $guard->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $this->assertFalse($story->isStopped());

        $story->withMessage($message);

        $tracker->disclose($story);

        $this->assertFalse($story->isStopped());
    }

    public function testExceptionRaisedWhenAuthorizationFailed(): void
    {
        $this->expectException(UnauthorizedException::class);

        $tracker = new TrackMessage();

        $messageName = SomeCommand::class;
        $message = new Message(new SomeCommand(['foo' => 'bar']), [Header::EVENT_TYPE => $messageName]);

        $this->messageAlias
            ->expects($this->once())
            ->method('classToAlias')
            ->with($messageName)
            ->willReturn($messageName);

        $this->authorization
            ->expects($this->once())
            ->method('isNotGranted')
            ->with($messageName, $message, GuardCommand::class)
            ->willReturn(true);

        $guard = new GuardCommand($this->authorization, $this->messageAlias);
        $guard->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $this->assertFalse($story->isStopped());

        $story->withMessage($message);

        $tracker->disclose($story);

        $this->assertTrue($story->isStopped());
    }
}
