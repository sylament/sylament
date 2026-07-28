<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

trait HasSchema
{
    /**
     * @var array<Component|Action|ActionGroup>|Closure|null
     */
    protected array|Closure|null $schema = null;

    protected bool|Closure $isSchemaDisabled = false;

    protected bool|Closure|null $hasFormWrapper = null;

    /**
     * @param  array<Component|Action|ActionGroup>|Closure|null  $schema
     */
    public function schema(array|Closure|null $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    public function disabledSchema(bool|Closure $condition = true): static
    {
        $this->isSchemaDisabled = $condition;

        return $this;
    }

    public function isSchemaDisabled(): bool
    {
        return (bool) $this->evaluate($this->isSchemaDisabled);
    }

    /**
     * Mirrors Filament's Concerns/HasSchema::getSchema(), minus two branches
     * this port doesn't carry: the array-becomes-Wizard arm (no Wizard
     * component exists yet — see docs/progress/actions/has-actions.md) and
     * the `$this->schema ?? $liveComponent?->getDefaultActionSchemaResolver($this)`
     * fallback (the `getDefaultAction*` host override hooks aren't ported
     * either, same doc). `$this->schema` alone drives resolution here; a
     * caller wanting a dynamic default schema supplies it directly via
     * schema().
     *
     * The `'schema' => $schema` named injection below always wins over
     * Action's own resolveDefaultClosureDependencyForEvaluationByName('schema')
     * (which resolves to getSchemaContainer()): Invoker::call checks
     * array_key_exists() against $namedInjections before it ever calls the
     * $resolveParameter callback, so a `fn (Schema $schema) => [...]` passed
     * to schema() receives the Schema being built by this call, not the
     * action's pre-existing schema container.
     *
     * Divergence: Filament also tolerates a schema()-closure returning a bare
     * Schema instance directly, guarding the array-to-Schema conversion below
     * with an is_array() check either way. `$schema` is declared
     * array|Closure|null here (Filament-identical — its own equivalent
     * property carries the same narrow union), so that possibility is
     * already invisible to the type system in Filament's own source; this
     * port's evaluate() additionally carries a PHPStan generic
     * (`@template T`, CLAUDE.md "Closure typing convention") that Filament's
     * does not, which turns that latent gap into an enforced one —
     * is_array($modifiedSchema) is always true given $schema's own declared
     * type, so PHPStan rejects it as redundant. Untested either side (no
     * schema()-closure in this codebase returns anything but an array), so
     * the branch is dropped rather than carried as dead code.
     */
    public function getSchema(Schema $schema): ?Schema
    {
        $modifiedSchema = $this->evaluate($this->schema, [
            'form' => $schema,
            'schema' => $schema,
            'infolist' => $schema,
        ]);

        if ($modifiedSchema === null) {
            return null;
        }

        if ($modifiedSchema === []) {
            return null;
        }

        $modifiedSchema = $schema->components($modifiedSchema);

        if ($this->isSchemaDisabled()) {
            return $modifiedSchema->disabled();
        }

        return $modifiedSchema;
    }

    public function formWrapper(bool|Closure|null $condition = true): static
    {
        $this->hasFormWrapper = $condition;

        return $this;
    }

    /**
     * Filament defaults to `! $this->isWizard()`; no Wizard component is
     * ported (see docs/progress/actions/has-actions.md), so this always
     * defaults to true unless overridden via formWrapper(false).
     */
    public function hasFormWrapper(): bool
    {
        return (bool) ($this->evaluate($this->hasFormWrapper) ?? true);
    }
}
