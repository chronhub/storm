<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Reporter\CommandReporter;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Reporter\Exceptions\MessageNotHandled;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\ReportCommand;
use Chronhub\Storm\Reporter\Subscribers\ConsumeCommand;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

#[CoversClass(ReportCommand::class)]
#[CoversClass(MessageNotHandled::class)]
final class ReportCommandTest extends UnitTestCase
{
    private MessageFactory|MockObject $messageFactory;

    private ReportCommand $reporter;

    protected function setUp(): void
    {
        $this->messageFactory = $this->createMock(MessageFactory::class);

        $tracker = new TrackMessage();
        $this->reporter = new ReportCommand($tracker);

        $this->assertInstanceOf(CommandReporter::class, $this->reporter);
        $this->assertEquals(DomainType::COMMAND, $this->reporter->getType());
        $this->assertSame($tracker, $this->reporter->tracker());
        $this->assertEmpty($this->reporter->tracker()->listeners());
    }

    public function testRelayCommand(): void
    {
        $command = SomeCommand::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($command)
            ->willReturn(new Message($command));

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

        $this->reporter->subscribe(...$subscribers);
        $this->reporter->relay($command);

        $this->assertTrue($messageHandled);
    }

    public function testRelayCommandAsArray(): void
    {
        $commandAsArray = ['some' => 'command'];
        $command = SomeCommand::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($commandAsArray)
            ->willReturn(new Message($command));

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

        $this->reporter->subscribe(...$subscribers);
        $this->reporter->relay($commandAsArray);

        $this->assertTrue($messageHandled);
    }

    #[DataProvider('provideCommand')]
    public function testExceptionRaisedWhenCommandNotMarkedAsHandled(DomainCommand $command, string $messageName): void
    {
        $this->expectException(MessageNotHandled::class);
        $this->expectExceptionMessage("Message with name $messageName was not handled");

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($command)
            ->willReturn(new Message($command));

        $subscribers = [
            new MakeMessage($this->messageFactory),
            new ConsumeCommand(),
        ];

        $this->reporter->subscribe(...$subscribers);
        $this->reporter->relay($command);
    }

    public function testExceptionRaisedDuringDispatch(): void
    {
        $exception = new RuntimeException('some exception');

        $this->expectException($exception::class);
        $this->expectExceptionMessage('some exception');

        $command = SomeCommand::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($command)
            ->willReturn(new Message($command));

        $consumer = function (DomainCommand $dispatchedCommand) use ($exception): never {
            $this->assertInstanceOf(SomeCommand::class, $dispatchedCommand);

            throw $exception;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeCommand(),
        ];

        $this->reporter->subscribe(...$subscribers);
        $this->reporter->relay($command);
    }

    public static function provideCommand(): Generator
    {
        $event = SomeCommand::fromContent(['name' => 'steph bug']);

        yield [$event, SomeCommand::class];
        yield [$event->withHeader(Header::EVENT_TYPE, 'some.command'), 'some.command'];
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
                $this->listeners[] = $tracker->watch(
                    Reporter::DISPATCH_EVENT,
                    function (MessageStory $story): void {
                        $story->withConsumers($this->consumers);
                    }, OnDispatchPriority::ROUTE->value);
            }
        };
    }
}
