<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Auth;

use Chronhub\Storm\Auth\GuardQueryOnDispatch;
use Chronhub\Storm\Auth\UnauthorizedException;
use Chronhub\Storm\Contracts\Auth\AuthorizeMessage;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\MockObject\MockObject;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class GuardQueryOnDispatchTest extends UnitTestCase
{
    private MessageAlias|MockObject $messageAlias;

    private AuthorizeMessage|MockObject $authorization;

    protected function setUp(): void
    {
        $this->messageAlias = $this->createMock(MessageAlias::class);
        $this->authorization = $this->createMock(AuthorizeMessage::class);
    }

    public function testGrantQuery(): void
    {
        $tracker = new TrackMessage();

        $messageName = SomeQuery::class;
        $message = new Message(new SomeQuery(['foo' => 'bar']), [Header::EVENT_TYPE => $messageName]);

        $this->messageAlias
            ->expects($this->once())
            ->method('classToAlias')
            ->with($messageName)
            ->willReturn($messageName);

        $this->authorization
            ->expects($this->once())
            ->method('isNotGranted')
            ->with($messageName, $message, null)
            ->willReturn(false);

        $guard = new GuardQueryOnDispatch($this->authorization, $this->messageAlias);
        $guard->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $this->assertFalse($story->isStopped());

        $story->withMessage($message);
        $story->withPromise($this->providePromise());

        $tracker->disclose($story);

        $this->assertPromise($story->promise());
    }

    public function testWhenAuthorizationFailedWhilePromiseHoldException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $tracker = new TrackMessage();

        $messageName = SomeQuery::class;
        $message = new Message(new SomeQuery(['foo' => 'bar']), [Header::EVENT_TYPE => $messageName]);

        $this->messageAlias
            ->expects($this->once())
            ->method('classToAlias')
            ->with($messageName)
            ->willReturn($messageName);

        $this->authorization
            ->expects($this->once())
            ->method('isNotGranted')
            ->with($messageName, $message, null)
            ->willReturn(true);

        $guard = new GuardQueryOnDispatch($this->authorization, $this->messageAlias);
        $guard->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $this->assertFalse($story->isStopped());

        $story->withMessage($message);
        $story->withPromise($this->providePromise());

        try {
            $tracker->disclose($story);
        } catch (UnauthorizedException $exception) {
            $this->assertTrue($story->isStopped());

            throw $exception;
        }
    }

    private function providePromise(): PromiseInterface
    {
        $deferred = new Deferred();

        $deferred->resolve(['foo' => 'bar']);

        return $deferred->promise();
    }

    private function assertPromise(PromiseInterface $promise): void
    {
        $data = null;

        $promise->then(function (array $result) use (&$data) {
            $data = $result;
        });

        $this->assertSame(['foo' => 'bar'], $data);
    }
}
