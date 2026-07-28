<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Schemas\Schema;

trait CanBeMounted
{
    protected ?Closure $mountUsing = null;

    /**
     * Filament wraps this call in a try/finally that calls
     * clearVisibilityCache() — this package has no visibility cache (nothing
     * under packages/actions/src caches isVisible()/isHidden() results), so
     * there is nothing to clear and the finally is not ported.
     *
     * @param array<string, mixed> $parameters
     */
    public function mount(array $parameters): mixed
    {
        return $this->evaluate($this->getMountUsing(), $parameters);
    }

    public function mountUsing(?Closure $callback): static
    {
        $this->mountUsing = $callback;

        return $this;
    }

    /**
     * Filament types this closure's first parameter `Action $action` and
     * calls `$action->evaluate($data)`. Here the closure omits that
     * parameter and calls `$this->evaluate($data)` instead: `mountUsing` is
     * stored on `$this`, and `mount()` always evaluates it back through that
     * very same instance (Invoker::call() never rebinds a closure's `$this`,
     * packages/support/src/Evaluation/Invoker.php:95), so a non-static
     * closure's captured `$this` is always the value an `$action` parameter
     * would have received. An untyped `$action` parameter would still
     * resolve correctly at runtime (by name, against `evaluationIdentifier`),
     * but its static type is `mixed`, so PHPStan cannot verify a method call
     * on it once this trait is actually composed onto a class; `$this`,
     * resolved through ordinary trait-in-context analysis, is properly typed
     * as the composing class instead.
     *
     * @param array<string, mixed>|Closure|null $data
     */
    public function fillForm(array|Closure|null $data): static
    {
        $this->mountUsing(function (?Schema $schema) use ($data): void {
            $schema?->fill($this->evaluate($data));
        });

        return $this;
    }

    public function getMountUsing(): Closure
    {
        return $this->mountUsing ?? static fn (?Schema $schema = null): ?Schema => $schema?->fill();
    }
}
