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
     * Wider than the `schema()` setter by one member: a `Closure` stored here
     * may return a `Schema` rather than a component array, which Filament
     * tolerates at runtime (its `getSchema()` guards the array-to-Schema
     * conversion with `is_array()` precisely so a closure can hand back a
     * prepared `Schema`). Filament's own property is the narrower
     * `array|Closure|null`, so that capability is invisible to its type
     * system; this port's `evaluate()` carries a PHPStan generic Filament's
     * does not, which would turn the same latent gap into a hard error. The
     * native type is widened rather than the branch dropped, so the
     * capability survives with the guard live instead of dead. The public
     * setter stays Filament-exact — a bare `Schema` still cannot be assigned
     * directly, only returned from a closure.
     *
     * @var Schema|array<Component|Action|ActionGroup>|Closure|null
     */
    protected Schema|array|Closure|null $schema = null;

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
     * A closure may return either a component array or an already-built
     * Schema; the is_array() guard is what lets both through, and is why
     * the backing property is typed wider than the setter (see above).
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

        if (is_array($modifiedSchema)) {
            $modifiedSchema = $schema->components($modifiedSchema);
        }

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
