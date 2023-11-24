<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\AliasFromInflector;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AliasFromInflector::class)]
final class AliasFromInflectorTest extends UnitTestCase
{
    public function testInstanceFromStringClass(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromInflector();

        $this->assertSame('some-command', $messageAlias->classToAlias($event::class));
    }

    public function testInstanceFromObject(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromInflector();

        $this->assertSame('some-command', $messageAlias->instanceToAlias($event));
    }

    public function testExceptionRaisedWhenGivenClassIsNotFqn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event class invalid_event does not exists');

        $messageAlias = new AliasFromInflector();

        /** @phpstan-ignore-next-line */
        $messageAlias->classToAlias('invalid_event');
    }
}
