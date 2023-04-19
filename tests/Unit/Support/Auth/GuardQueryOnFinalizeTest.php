<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support\Auth;

use Chronhub\Storm\Contracts\Auth\AuthorizeMessage;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Support\Auth\GuardQueryOnFinalize;
use Chronhub\Storm\Support\Auth\UnauthorizedException;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;

#[CoversClass(GuardQueryOnFinalize::class)]
class GuardQueryOnFinalizeTest extends UnitTestCase
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
            ->with($messageName, $message, ['foo' => 'bar'])
            ->willReturn(false);

        $guard = new GuardQueryOnFinalize($this->authorization, $this->messageAlias);
        $guard->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::FINALIZE_EVENT);
        $this->assertFalse($story->isStopped());

        $story->withMessage($message);
        $story->withPromise($this->providePromise());

        $tracker->disclose($story);

        $this->assertPromise($story->promise());
    }

    public function testAuthorizationFailedWhilePromiseHoldException(): void
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
            ->with($messageName, $message, ['foo' => 'bar'])
            ->willReturn(true);

        $guard = new GuardQueryOnFinalize($this->authorization, $this->messageAlias);
        $guard->attachToReporter($tracker);

        $story = $tracker->newStory(Reporter::FINALIZE_EVENT);
        $this->assertFalse($story->isStopped());

        $story->withMessage($message);
        $story->withPromise($this->providePromise());

        $tracker->disclose($story);

        $e = null;
        $story->promise()->then(null, static function (Throwable $exception) use (&$e) {
            $e = $exception;
        });

        $this->assertInstanceOf(UnauthorizedException::class, $e);
        $this->assertTrue($story->isStopped());
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

        $promise->then(static function (array $result) use (&$data) {
            $data = $result;
        });

        $this->assertSame(['foo' => 'bar'], $data);
    }
}
