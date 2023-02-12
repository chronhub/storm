<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Message\AliasFromClassName;

final class AliasFromClassnameTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_return_event_class_from_event_string(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromClassName();

        $this->assertEquals($event::class, $messageAlias->classToAlias($event::class));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_event_class_string_does_not_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event class invalid_event does not exists');

        $messageAlias = new AliasFromClassname();

        $messageAlias->classToAlias('invalid_event');
    }

    /**
     * @test
     */
    public function it_return_event_class_from_event_object(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromClassname();

        $this->assertEquals($event::class, $messageAlias->instanceToAlias($event));
    }
}
