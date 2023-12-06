<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\ReadModel;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Illuminate\Support\Collection;

use function abs;

final class InMemoryReadModel implements ReadModel
{
    use InteractWithStack;

    private bool $initialized = false;

    /**
     * @var Collection<string,array>
     */
    private Collection $container;

    public function __construct()
    {
        $this->container = new Collection();
    }

    public function initialize(): void
    {
        $this->initialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function reset(): void
    {
        $this->container = new Collection();
    }

    public function down(): void
    {
        $this->reset();
    }

    public function getContainer(): array
    {
        return $this->container->all();
    }

    protected function insert(string $id, array $data): void
    {
        $this->container->put($id, $data);
    }

    protected function update(string $id, string $field, mixed $value): void
    {
        $data = $this->container->get($id);

        $data[$field] = $value;

        $this->container->put($id, $data);
    }

    protected function increment(string $id, string $field, int|float $value, array $extra = []): void
    {
        $this->adjust($id, $field, $value, true, $extra);
    }

    protected function decrement(string $id, string $field, int|float $value, array $extra = []): void
    {
        $this->adjust($id, $field, $value, false, $extra);
    }

    protected function delete(string $id): void
    {
        $this->container->forget($id);
    }

    private function adjust(string $id, string $field, int|float $value, bool $increment = true, array $extra = []): void
    {
        $data = $this->container->get($id);

        $data[$field] += ($increment ? 1 : -1) * abs($value);

        $this->container->put($id, $data);

        foreach ($extra as $extraField => $extraValue) {
            $this->update($id, $extraField, $extraValue);
        }
    }
}
