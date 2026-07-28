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
     * Filament types this closure's first parameter `Action $action`. Here it
     * is left untyped and resolved by name instead, against the
     * `evaluationIdentifier` set on {@see \Sylament\Actions\Action} ('action').
     * The Invoker's type-based resolution (`is_a($this, $parameterType)`,
     * packages/support/src/Concerns/EvaluatesClosures.php:55) resolves an
     * `Action`-typed parameter to the evaluating instance identically to the
     * name-based path when that instance really is an Action — but the
     * Invoker never casts: PHP itself enforces the declared parameter type at
     * the `$callable(...$args)` call (packages/support/src/Evaluation/Invoker.php:95),
     * regardless of which path resolved the value. An `Action` type hint would
     * therefore reject this trait's own bare Unit-test fixture, which is not
     * an Action. Leaving the parameter untyped and matching by name carries no
     * such constraint and is exactly as reliable wherever this trait is
     * actually used (only Action, which declares evaluationIdentifier =
     * 'action').
     *
     * @param array<string, mixed>|Closure|null $data
     */
    public function fillForm(array|Closure|null $data): static
    {
        $this->mountUsing(static function ($action, ?Schema $schema) use ($data): void {
            $schema?->fill($action->evaluate($data));
        });

        return $this;
    }

    public function getMountUsing(): Closure
    {
        return $this->mountUsing ?? static fn (?Schema $schema = null): ?Schema => $schema?->fill();
    }
}
