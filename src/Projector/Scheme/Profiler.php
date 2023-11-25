<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Throwable;

use function count;
use function memory_get_usage;
use function microtime;

final class Profiler
{
    /**
     * @var array<positive-int, array>
     */
    private array $cycles = [];

    private int $count = 0;

    public function start(Subscription $subscription): void
    {
        if ($this->isNotValid()) {
            return;
        }

        $this->count++;

        $currentCycle['start'] = $this->provideData($subscription);
        $currentCycle['error'] = null;

        $this->cycles[$this->count] = $currentCycle;
    }

    public function end(Subscription $subscription, Throwable $exception = null): void
    {
        if ($this->isNotValid()) {
            return;
        }

        $index = $this->count;

        $this->cycles[$index]['end'] = $this->provideData($subscription);

        if ($exception) {
            $this->cycles[$index]['error'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getData(): array
    {
        return $this->cycles;
    }

    private function isNotValid(): bool
    {
        return $this->count !== count($this->cycles);
    }

    private function provideData(Subscription $subscription): array
    {
        return [
            'status' => $subscription->currentStatus(),
            'memory' => memory_get_usage(true),
            'at' => microtime(true),
        ];
    }
}
