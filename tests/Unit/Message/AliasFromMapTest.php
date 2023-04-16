<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\AliasFromMap;
use Chronhub\Storm\Message\MessageAliasNotFound;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AliasFromMap::class)]
final class AliasFromMapTest extends UnitTestCase
{
    public function testReturnAlias(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $map = [$event::class => 'message_alias'];

        $messageAlias = new AliasFromMap($map);

        $this->assertEquals('message_alias', $messageAlias->classToAlias($event::class));
    }

    public function testReturnAliasFromEventInstance(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $map = [$event::class => 'message_alias'];

        $messageAlias = new AliasFromMap($map);

        $this->assertEquals('message_alias', $messageAlias->instanceToAlias($event));
    }

    public function testExceptionRaisedWhenEventGivenIsNotFqn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event class invalid_event does not exists');

        $messageAlias = new AliasFromMap([]);

        $messageAlias->classToAlias('invalid_event');
    }

    public function testExceptionRaisedWhenEventGivenNotFoundInMap(): void
    {
        $this->expectException(MessageAliasNotFound::class);

        $messageAlias = new AliasFromMap([]);

        $messageAlias->classToAlias(SomeCommand::class);
    }
}
