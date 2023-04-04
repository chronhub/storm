<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\Attribute\AsDomainQuery;
use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Reporter\DomainCommand;

#[AsDomainQuery(['foo' => 'bar'], 'query', 'SomeQueryHandler')]
class AsDomainQueryStub extends DomainCommand
{
    use HasConstructableContent;
}
