<?php

declare(strict_types=1);

namespace Sylament\Schemas\Contracts;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

interface InteractsWithSchemas
{
    /**
     * @return array<string>
     */
    public function schemas(): array;

    public function getSchema(string $name): Schema;

    /**
     * Resolve a schema by name the way a browser-supplied `schemaComponent`
     * key must: a cached schema wins; otherwise a `<name>()` / `<name>Schema()`
     * method is accepted only after a reflection gate — it may not be a member
     * this interface declares, and its first parameter (its only required one),
     * or failing that its return type, must be a {@see Schema} — and a name
     * the gate refuses answers `null` rather than throwing. **Never resolve a
     * browser-supplied name through {@see getSchema()} instead**: it invokes
     * any same-named single-argument host method, which from a `#[LiveArg]`
     * would be arbitrary method invocation — this reflection gate is what
     * stands between the two. What the gate admits beyond real schema
     * builders, exactly as Filament's does: a host's own parameterless
     * `Schema`-returning getter, invoked and keyed under the browser's name
     * for that request.
     */
    public function resolveSchemaByName(string $name): ?Schema;

    /**
     * Resolve a component, action or action group anywhere across this
     * host's schemas by an absolute `<schemaName>.<key>` address (no `.` in
     * `$key` answers `null`). The schema name is resolved through
     * {@see resolveSchemaByName()}.
     */
    public function getSchemaComponent(string $key, bool $withHidden = false): Component|Action|ActionGroup|null;

    /**
     * Dispatch a browser event on the host's own root, riding the next
     * render — the Sylament translation of Filament's
     * `$this->getLivewire()->dispatch(...)`, which Livewire's component type
     * carries implicitly and ours has to declare explicitly. A Sylament-only
     * contract member: there is no Filament source, because Livewire returns
     * values from an action and UX LiveComponent cannot. A host built with
     * `new` rather than resolved from the container has no injected responder
     * and throws when this is called; every real (container-built) host has
     * one.
     *
     * @param array<mixed> $payload
     */
    public function dispatchBrowserEvent(string $eventName, array $payload = []): void;

    /**
     * The generic browser-callable seam behind the JS select and, later,
     * Wizard and Tabs. Resolves `$componentKey` through
     * {@see getSchemaComponent()}, then calls `$method` only when it exists
     * on the resolved component, carries the
     * `Sylament\Support\Components\Attributes\ExposedLiveComponentMethod`
     * attribute, and the supplied `$arguments` match its signature (every
     * key names a parameter, every parameter without a default is present) —
     * otherwise the result is `null`, never a throw or a 500, including when
     * a resolved argument is of the wrong type. The result always travels
     * back as the browser event `sylament:schema-component-method-result`
     * with payload `{componentKey, method, result}`, never as this method's
     * own return value.
     *
     * @param array<mixed> $arguments
     */
    public function callSchemaComponentMethod(string $componentKey, string $method, array $arguments = []): void;

    public function makeSchema(): Schema;

    /**
     * Register a schema instance under a name, or unset it by passing null.
     * The registered instance is keyed with `->key($name)`, exactly as the
     * `{$name}()` / `{$name}Schema()` discovery path keys what it builds.
     */
    public function cacheSchema(string $name, ?Schema $schema): ?Schema;

    /**
     * Whether a schema is already registered under this name. Deliberately does
     * not trigger discovery: callers use it to tell a cache-only schema (a
     * mounted action's, minted server-side) from a method-declared one.
     */
    public function hasCachedSchema(string $name): bool;

    /**
     * The pre-change raw state snapshotted for the given absolute state path at
     * the end of the previous render, used by the `afterStateUpdated` diff.
     */
    public function getOldSchemaState(?string $path): mixed;

    /**
     * Run the validator over the schema's collected constraints against the raw
     * live state. Failures fill the error bag; on the submit path (default) a
     * failure also records the schema identity sticky, announces
     * `form-validation-error` to the browser and throws, while success clears
     * both. The silent path only rewrites the bag section. The identity is the
     * schema's own key for a root schema and the owning component's absolute
     * key for a component's child schema, so validating one step of a wizard
     * cannot overwrite the whole form's bag.
     */
    public function validateSchema(Schema $schema, bool $shouldThrow = true): void;

    /**
     * Run the validator over the record a schema has just mapped, folding each
     * violation into the same error bag under the state path that owns it — the
     * schema's own path for a violation no declared path claims — recording the
     * schema key sticky and throwing when any exist. A host holding no validator
     * (one built outside the container) skips the run rather than refusing to
     * map at all.
     */
    public function validateSchemaRecord(Schema $schema, object $record): void;

    /**
     * All bag messages recorded for an absolute state path; null returns none.
     *
     * @return list<string>
     */
    public function getErrorsForSchemaStatePath(?string $path): array;

    /**
     * Drop one schema's stickiness and bag section, or all of both when the key
     * is null.
     */
    public function clearSchemaValidation(?string $schemaKey = null): void;
}
