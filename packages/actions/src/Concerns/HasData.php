<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

trait HasData
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?Closure $mutateDataUsing = null;

    public function mutateDataUsing(?Closure $callback): static
    {
        $this->mutateDataUsing = $callback;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function data(array $data, bool $shouldMutate = true): static
    {
        if ($shouldMutate && $this->mutateDataUsing) {
            $data = $this->evaluate($this->mutateDataUsing, [
                'data' => $data,
            ]);
        }

        $this->data = $data;

        return $this;
    }

    public function resetData(): static
    {
        $this->data([], shouldMutate: false);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Filament reads `$this->getLivewire()->mountedActions[$this->getNestingIndex()]['data'] ?? []` —
     * a direct property read into Livewire's public array prop. We cannot mirror that as a property
     * read: Sylament\Schemas\State\PropertyAccessor resolves a path like 'mountedActions[0][data]'
     * through the same-named getMountedActions() getter (Symfony's ReflectionExtractor prefers a
     * getter over the property), which returns resolved Action objects, not the raw dehydrated bag —
     * NoSuchIndexException on every read. That is the same trap that already forces
     * `dehydrateWith: 'dehydrateMountedActions'` elsewhere. So the live component exposes a dedicated
     * sibling bag, `mountedActionsData`, via InteractsWithActions::getMountedActionData(), and this
     * reads through that contract method instead of the bag directly.
     *
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->getLiveComponentOrNull()?->getMountedActionData($this->getNestingIndex()) ?? [];
    }
}
