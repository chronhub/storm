<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CommandGroup::class)]
final class CommandGroupTest extends UnitTestCase
{
    public function testGroupType(): void
    {
        $routes = new CollectRoutes(new AliasFromClassName());

        $this->assertEquals(DomainType::COMMAND, (new CommandGroup('default', $routes))->getType());
    }
}
