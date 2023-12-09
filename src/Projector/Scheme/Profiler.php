<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\StateManagement;
use Closure;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_keys;
use function array_values;
use function count;
use function memory_get_usage;
use function microtime;

final class Profiler
{
    private array $cycles = [];

    private int $index = 0;

    public function __construct(private ?OutputInterface $output = null)
    {
    }

    public function start(StateManagement $subscription): void
    {
        if (! $this->isValid()) {
            return;
        }

        $this->index++;

        $currentCycle['start'] = $this->provideData($subscription);
        $currentCycle['error'] = null;

        $this->cycles[$this->index] = $currentCycle;

        if ($this->output) {
            $this->output("Start cycle $this->index", $currentCycle);
        }
    }

    /**
     * @param array<string, int> $streamNames
     */
    public function inStreams(array $streamNames): void
    {
        if (! $this->isValid()) {
            return;
        }

        foreach ($streamNames as $streamName => $count) {
            if (! isset($this->cycles[$this->index]['streams'][$streamName])) {
                continue;
            }

            $this->cycles[$this->index]['streams'][$streamName] = [
                'loaded' => $count,
                'handled' => 0,
                'gap' => null,
            ];
        }
    }

    public function inEvent(string $streamName, int $eventPosition, bool $eventHandled, bool $hasGap): void
    {
        if (! $this->isValid() || ! isset($this->cycles[$this->index]['streams'][$streamName])) {
            return;
        }

        $index = $this->index;

        if ($eventHandled) {
            $this->cycles[$index]['streams'][$streamName]['handled']++;
        } elseif ($hasGap) {
            $this->cycles[$index]['streams'][$streamName]['gap'] = $eventPosition;
        }
    }

    public function end(StateManagement $subscription, Throwable $exception = null): void
    {
        if (! $this->isValid()) {
            return;
        }

        $index = $this->index;

        $this->cycles[$index]['end'] = $this->provideData($subscription);

        if ($exception) {
            $this->cycles[$index]['error'] = class_basename($exception::class);
        }

        $this->output("End cycle $index", $this->cycles[$index]);
    }

    public function setOutput(OutputInterface|Closure $output): void
    {
        if ($output instanceof Closure) {
            $output = $output();
        }

        $this->output = $output;
    }

    public function getRawData(): array
    {
        return $this->cycles;
    }

    private function output(string $message, array $data): void
    {
        if (! $this->output) {
            return;
        }

        $this->output->writeln("<info>$message</info>");

        $table = new Table($this->output);

        $table->setHeaders(array_keys($data));
        $table->addRow(array_values($data));

        $table->render();

        $this->output->writeln('');
    }

    private function isValid(): bool
    {
        return $this->index === count($this->cycles);
    }

    private function provideData(StateManagement $subscription): array
    {
        return [
            'status' => $subscription->currentStatus(),
            'memory' => memory_get_usage(true),
            'at' => microtime(true),
        ];
    }
}
