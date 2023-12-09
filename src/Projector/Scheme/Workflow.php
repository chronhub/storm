<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\Subscriber;
use Closure;

use function array_reduce;
use function array_reverse;

final readonly class Workflow
{
    /**
     * @param array<callable> $activities
     */
    public function __construct(
        private Subscriber $subscription,
        private array $activities
    ) {
    }

    public function process(Closure $destination): bool
    {
        $process = array_reduce(
            array_reverse($this->activities),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $process($this->subscription);
    }

    private function prepareDestination(Closure $destination): Closure
    {
        return fn (Subscriber $subscription) => $destination($subscription);
    }

    private function carry(): Closure
    {
        return fn ($stack, $activity) => fn (Subscriber $subscription) => $activity($subscription, $stack);
    }
}
