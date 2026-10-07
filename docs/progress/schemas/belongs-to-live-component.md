---
description: The trait that binds a Schema to its UX LiveComponent host, with a throwing accessor and a nullable peek.
ports:
  - vendor/filament/schemas/src/Concerns/BelongsToLivewire.php
owns:
  - packages/schemas/src/Concerns/BelongsToLiveComponent.php
---
# BelongsToLiveComponent (schemas)

[`Sylament\Schemas\Concerns\BelongsToLiveComponent`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php) binds a [`Schema`](schema.md) to the UX LiveComponent host it lives on: a nullable `liveComponent` property, the `liveComponent()` setter, and the `getLiveComponent()` accessor.
It ports Filament's `BelongsToLivewire`, renamed under the Livewire → UX LiveComponent idiom translation.
The actions package composes its own [`Sylament\Actions\Concerns\BelongsToLiveComponent`](../../../packages/actions/src/Concerns/BelongsToLiveComponent.php), which resolves its host through this one ([`actions/action.md`](../actions/action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`liveComponent()`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php) | [`livewire()`](../../../vendor/filament/schemas/src/Concerns/BelongsToLivewire.php) | done | — | — |
| [`getLiveComponent()`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php) | [`getLivewire()`](../../../vendor/filament/schemas/src/Concerns/BelongsToLivewire.php) | done | — | [diverges](#getlivecomponent-throws-instead-of-returning-a-possibly-null-component) |
| [`getLiveComponentOrNull()`](../../../packages/schemas/src/Concerns/BelongsToLiveComponent.php) | — (Sylament-only) | done | — | [diverges](#getlivecomponent-throws-instead-of-returning-a-possibly-null-component) |

## Divergences

### `getLiveComponent()` throws instead of returning a possibly-null component

Filament types `getLivewire(): Component & HasSchemas` but returns the nullable `$livewire` property directly, letting a `null` propagate downstream.
Sylament's `getLiveComponent()` throws `RuntimeException('Schema is not part of LiveComponent')` when unbound, failing fast instead of falling back silently.
`Schema`'s closure injection reads the raw property, so a standalone schema still evaluates closures with `$liveComponent = null` rather than throwing.
The property type also collapses Filament's `Livewire\Component & HasSchemas` intersection to the bare host contract [`InteractsWithSchemas`](interacts-with-schemas.md): UX LiveComponents have no marker base class to intersect with.

The throw creates one need Filament does not have: a caller probing for an *optional* binding.
`getLiveComponentOrNull()` is that Sylament-only public peek, and it returns the raw nullable property.
The actions package's live-component fallback chain reads it while it walks `getSchemaContainer()` and `getSchemaComponent()` looking for a host, and it must get `null`, not an exception, from an unbound schema.
[`HasChildComponents::makeChildSchema()`](component.md#haschildcomponents-only-the-materialization-core-is-ported) binds a materialized child `Schema` to the parent component's live component or to `null`, so a component whose container has no live component (a preview render, or any `Schema::make()` built with no host) still materializes an array-valued child slot.
In Filament the nullable `getLivewire()` itself plays this role, so there is no counterpart method to port.
The component-level twin lives on [`BelongsToContainer`](component.md#belongstocontainer-gains-a-sylament-only-getlivecomponentornull-peek), delegating through the container back-reference.
