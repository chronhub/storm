<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Closure;
use Chronhub\Storm\Contracts\Projector\Subscription;
use function array_reduce;
use function array_reverse;

final readonly class Workflow
{
    private function __construct(
        private Subscription $subscription,
        private array $activities
    ) {
    }

    public static function carry(Subscription $subscription, array $activities): self
    {
        return new self($subscription, $activities);
    }

    public function process(Closure $destination): bool
    {
        $execute = array_reduce(
            array_reverse($this->activities),
            $this->funnel(),
            $this->prepareDestination($destination)
        );

        return $execute($this->subscription);
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return static fn (Subscription $subscription) => $destination($subscription);
    }

    protected function funnel(): Closure
    {
        return static fn (Closure $stack, callable $activity) => static fn (Subscription $subscription) => $activity($subscription, $stack);
    }
}
