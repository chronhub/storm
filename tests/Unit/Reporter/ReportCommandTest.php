<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Generator;
use RuntimeException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Reporter\ReportCommand;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Contracts\Message\Header;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Reporter\Subscribers\ConsumeCommand;
use Chronhub\Storm\Reporter\Exceptions\MessageNotHandled;

#[CoversClass(ReportCommand::class)]
#[CoversClass(MessageNotHandled::class)]
final class ReportCommandTest extends UnitTestCase
{
    private MessageFactory|MockObject $messageFactory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->messageFactory = $this->createMock(MessageFactory::class);
    }

    #[Test]
    public function it_relay_command(): void
    {
        $command = SomeCommand::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($command)
            ->willReturn(new Message($command));

        $tracker = new TrackMessage();

        $reporter = new ReportCommand($tracker);

        $this->assertSame($tracker, $reporter->tracker());

        $messageHandled = false;

        $consumer = function (DomainCommand $dispatchedCommand) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeCommand::class, $dispatchedCommand);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeCommand(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($command);

        $this->assertTrue($messageHandled);
    }

    #[Test]
    public function it_relay_command_as_array(): void
    {
        $commandAsArray = ['some' => 'command'];
        $command = SomeCommand::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($commandAsArray)
            ->willReturn(new Message($command));

        $tracker = new TrackMessage();

        $reporter = new ReportCommand($tracker);

        $this->assertSame($tracker, $reporter->tracker());

        $messageHandled = false;

        $consumer = function (DomainCommand $dispatchedCommand) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeCommand::class, $dispatchedCommand);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeCommand(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($commandAsArray);

        $this->assertTrue($messageHandled);
    }

    #[Test]
    #[DataProvider('provideCommand')]
    public function it_raise_exception_when_message_has_not_been_handled(DomainCommand $command, string $messageName): void
    {
        $this->expectException(MessageNotHandled::class);
        $this->expectExceptionMessage("Message with name $messageName was not handled");

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($command)
            ->willReturn(new Message($command));

        $tracker = new TrackMessage();
        $reporter = new ReportCommand($tracker);

        $subscribers = [
            new MakeMessage($this->messageFactory),
            new ConsumeCommand(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($command);
    }

    #[Test]
    public function it_raise_exception_caught_during_dispatch_of_command(): void
    {
        $exception = new RuntimeException('some exception');

        $this->expectException($exception::class);
        $this->expectExceptionMessage('some exception');

        $command = SomeCommand::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($command)
            ->willReturn(new Message($command));

        $tracker = new TrackMessage();
        $reporter = new ReportCommand($tracker);

        $consumer = function (DomainCommand $dispatchedCommand) use ($exception): never {
            $this->assertInstanceOf(SomeCommand::class, $dispatchedCommand);

            throw $exception;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeCommand(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($command);
    }

    public static function provideCommand(): Generator
    {
        yield [SomeCommand::fromContent(['name' => 'steph bug']), SomeCommand::class];

        yield [SomeCommand::fromContent(['name' => 'steph bug'])
            ->withHeader(Header::EVENT_TYPE, 'some.command'), 'some.command',
        ];
    }

    private function provideRouter(array $consumers): MessageSubscriber
    {
        return new class($consumers) implements MessageSubscriber
        {
            private array $listeners = [];

            public function __construct(private readonly iterable $consumers)
            {
            }

            public function detachFromReporter(MessageTracker $tracker): void
            {
                foreach ($this->listeners as $listener) {
                    $tracker->forget($listener);
                }
            }

            public function attachToReporter(MessageTracker $tracker): void
            {
                $this->listeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, function (MessageStory $story): void {
                    $story->withConsumers($this->consumers);
                }, OnDispatchPriority::ROUTE->value);
            }
        };
    }
}
