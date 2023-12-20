<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\EventStream;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function array_unique;
use function count;

final readonly class DiscoverCategories
{
    public function __construct(private array $categories)
    {
        $this->validateCategories();
    }

    public function __invoke(EventStreamProvider $provider): array
    {
        return $provider->filterByAscendantCategories($this->categories);
    }

    private function validateCategories(): void
    {
        if ($this->categories === []) {
            throw new InvalidArgumentException('Categories cannot be empty');
        }

        if (count($this->categories) !== count(array_unique($this->categories))) {
            throw new InvalidArgumentException('Categories cannot contain duplicate');
        }
    }
}
