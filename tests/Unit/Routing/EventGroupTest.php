<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\EventGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EventGroup::class)]
final class EventGroupTest extends UnitTestCase
{
    public function testGroupType(): void
    {
        $routes = new CollectRoutes(new AliasFromClassName());

        $this->assertEquals(DomainType::EVENT, (new EventGroup('default', $routes))->getType());
    }
}
