# InteractsWithSchemas (live-component contract + trait)

The surface a UX LiveComponent hosting schemas exposes. Ours is the interface
`Sylament\Schemas\Contracts\InteractsWithSchemas` plus the trait
`Sylament\Schemas\Concerns\HasSchemas` — the pair is deliberately name-swapped
relative to Filament's `Contracts\HasSchemas` + `Concerns\InteractsWithSchemas`
(see [renames.md](../renames.md)). Ported so far: the schema name registry
(`schemas()`), `getSchema()` with per-name caching, `getCachedSchemas()`, and
`makeSchema()`.

Reference:

- [`vendor/filament/schemas/src/Contracts/HasSchemas.php`](../../../vendor/filament/schemas/src/Contracts/HasSchemas.php)
- [`vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php`](../../../vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php)

## Missing

Portable now (no blocker):

- [ ] Decide the fate of Filament's discovery conveniences the name registry
  replaced: the `{$name}Schema()` method fallback and the `default{Name}()`
  defaults hooks (see the divergence below).

Blocked on infrastructure:

- [ ] `getSchemaComponent()` (key-addressed lookup incl. nested child
  containers), `getOldSchemaState()`, `currentlyValidatingSchema()`, the
  validation/state plumbing, partial-render targeting, and
  `getDefaultTestingSchemaName()` — blocked on
  [live-component-state](../infrastructure.md#live-component-state). That epic
  also decides how the invented registry below coexists with the rest of
  Filament's 550-line trait.
- [ ] `makeFilamentTranslatableContentDriver()` — blocked on
  [translation](../infrastructure.md#translation).

## Divergences

- **Name registry instead of reflection discovery.** Filament discovers a
  live component's schemas by reflecting public `Schema`-returning methods and
  falls back to `{$name}Schema()`; our `schemas(): array` explicitly lists the
  schema names, and `getSchema($name)` calls the same-named method. Invented
  API surface — Filament has no `schemas()` method.
- **`getSchema()` throws for unknown names** (and for methods not returning a
  `Schema`); Filament returns `null` and its contract types `?Schema`.
  Deliberate fail-fast.
- **`makeSchema()` is public and part of the contract**; in Filament it is a
  protected trait helper. Needed publicly so `Schema::make($liveComponent)`
  stays overridable per host.
