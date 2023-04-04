<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Producer;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Producer\LogicalProducer;
use Chronhub\Storm\Producer\ProducerStrategy;
use Chronhub\Storm\Tests\Stubs\Double\SomeAsyncCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use ValueError;

final class LogicalProducerTest extends UnitTestCase
{
    #[DataProvider('provideSyncMessage')]
    #[Test]
    public function it_assert_message_is_sync(Message $message): void
    {
        $unity = new LogicalProducer();

        $this->assertTrue($unity->isSync($message));
    }

    #[DataProvider('provideAsyncMessage')]
    #[Test]
    public function it_assert_message_is_async(Message $message): void
    {
        $unity = new LogicalProducer();

        $this->assertFalse($unity->isSync($message));
    }

    #[DataProvider('provideInvalidEventDispatchedHeader')]
    #[Test]
    public function it_raise_exception_when_event_dispatched_header_is_invalid(?array $headers): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid producer event header: "__event_dispatched" is required and must be a boolean');

        $unity = new LogicalProducer();

        $unity->isSync(new Message(new SomeEvent([]), $headers ?? []));
    }

    #[DataProvider('provideInvalidEventStrategyHeader')]
    #[Test]
    public function it_raise_exception_when_event_strategy_header_is_invalid(array $headers): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid producer event header: "__event_strategy" is required and must be a string');

        $unity = new LogicalProducer();

        $unity->isSync(new Message(new SomeEvent([]), $headers));
    }

    #[Test]
    public function it_raise_exception_when_event_strategy_header_is_not_part_of_producer_strategy(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('"invalid_strategy" is not a valid backing value for enum');

        $unity = new LogicalProducer();

        $unity->isSync(new Message(new SomeEvent([]), [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => 'invalid_strategy',
        ]));
    }

    public static function provideSyncMessage(): Generator
    {
        $someEvent = SomeCommand::fromContent(['name' => 'steph bug']);

        yield [new Message($someEvent, [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::SYNC->value,
        ])];

        yield [new Message($someEvent, [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => ProducerStrategy::ASYNC->value,
        ])];

        yield [new Message($someEvent, [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];

        yield [new Message($someEvent, [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];

        yield [new Message(SomeAsyncCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];
    }

    public static function provideAsyncMessage(): Generator
    {
        $someEvent = SomeAsyncCommand::fromContent(['name' => 'steph bug']);

        yield [new Message($someEvent, [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::ASYNC->value,
        ])];

        yield [new Message(SomeAsyncCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => ProducerStrategy::PER_MESSAGE->value,
        ])];
    }

    public static function provideInvalidEventDispatchedHeader(): Generator
    {
        yield [null];
        yield [[Header::EVENT_DISPATCHED => 1]];
        yield [[Header::EVENT_DISPATCHED => 'dispatched']];
    }

    public static function provideInvalidEventStrategyHeader(): Generator
    {
        yield [[
            Header::EVENT_DISPATCHED => false,
            Header::EVENT_STRATEGY => 1,
        ]];

        yield [[
            Header::EVENT_DISPATCHED => true,
            Header::EVENT_STRATEGY => new stdClass(),
        ]];
    }
}
