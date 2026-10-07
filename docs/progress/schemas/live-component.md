---
description: The schema component that embeds another live component in place, by registered name or class-string, with the props the caller names.
ports:
  - vendor/filament/schemas/src/Components/Livewire.php
  - vendor/filament/schemas/src/Components/Concerns/HasKey.php
owns:
  - packages/schemas/src/Components/LiveComponent.php
  - packages/schemas/templates/partials/LiveComponent/
---
# LiveComponent (schemas)

[`Sylament\Schemas\Components\LiveComponent`](../../../packages/schemas/src/Components/LiveComponent.php) is the embed: a schema component that mounts **another live component** in place, by registered name (or class-string), with props the caller names.
It ports [`schemas/src/Components/Livewire.php`](../../../vendor/filament/schemas/src/Components/Livewire.php) under the systematic Livewire → UX LiveComponent idiom translation, so the class is renamed without a [rename table](../renames.md) row; the [Idiom list](../translations.md#idiom) carries the example.
Its template is [`partials/LiveComponent/index.html.twig`](../../../packages/schemas/templates/partials/LiveComponent/index.html.twig) ([component-views.md](component-views.md)).

It is the mechanism panels' relation managers mount through: an edit page's content schema holds one embed per manager, each keyed by the manager class (joined with the group key inside a relation group), and each embed's `id` is what keeps that manager's own state across the page's re-renders.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(string\|Closure $component, array\|Closure $data = [])`](../../../packages/schemas/src/Components/LiveComponent.php) | [`make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | [diverges](#the-two-constructor-arguments-arrive-through-setters) |
| [`component()` / `getComponent()`](../../../packages/schemas/src/Components/LiveComponent.php) | [`component()` / `getComponent()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | [semantics](#getcomponent-answers-a-name-not-a-component-object) |
| [`data()` / `getData()`](../../../packages/schemas/src/Components/LiveComponent.php) | [`data()` / `getData()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | — |
| [`getComponentProperties()`](../../../packages/schemas/src/Components/LiveComponent.php) — the mount props | [`getComponentProperties()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | [diverges](#the-embed-passes-no-implicit-record-prop) |
| [`getChildId()`](../../../packages/schemas/src/Components/LiveComponent.php) — the child's explicit live id | [`toEmbeddedHtml()`'s `$key`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | [diverges](#the-child-identity-is-an-id-prop-and-a-keyless-embed-throws) |
| [`getLiveComponentKey()`](../../../packages/schemas/src/Components/Concerns/HasKey.php) on the component-level `HasKey` | [`Components/Concerns/HasKey::getLivewireKey()`](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php) | done | — | [diverges](#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id) |
| [`InteractsWithSchemas::getSchemasHostId()`](../../../packages/schemas/src/Contracts/InteractsWithSchemas.php) + its [`HasSchemas`](has-schemas.md) implementation | [`getLivewire()->getId()`](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php) | done | — | [diverges](#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id) |
| [The partial's `component()` call](../../../packages/schemas/templates/partials/LiveComponent/index.html.twig) | [`LivewireFacade::mount()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | done | — | [diverges](#the-mount-is-a-twig-frame-not-a-php-call) |
| `lazy()` / `isLazy()` | [`Livewire.php`](../../../vendor/filament/schemas/src/Components/Livewire.php) | skipped | — | [diverges](#lazy-is-not-ported-because-uxs-lazy-defers-the-render-not-the-mount) |
| `getId()` / the `id`-or-extra-attributes wrapper `div` | [`toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Livewire.php) | skipped | — | [diverges](#there-is-no-wrapper-div-because-the-id-goes-on-the-child-itself) |
| `publishedViewOverrideCheckPath` (Blade view-override detection) | [`Livewire.php`](../../../vendor/filament/schemas/src/Components/Livewire.php) | skipped | — | — |

## Semantics

### `getComponent()` answers a name, not a component object

At the schema root tier `getComponent()` is the finder: [`Schema::getComponent($key)`](schema.md) walks the tree and returns a `Component`.
Here it answers the **string** the embed will mount: a registered `twig_component` name, or the class-string of any class-backed component, which `ComponentFactory::metadataFor()` resolves to that name through its class-string-keyed `$classMap` ([`ComponentFactory.php:78-81`](../../../vendor/symfony/ux-twig-component/src/ComponentFactory.php)), so a relation manager can be embedded by class-string whatever name panels registered it under.
The collision is Filament's, where both members carry these names too, and it is worth knowing before reading a call site.

### Preservation rides on the child declaring no updateFromParent props

A child with no `updateFromParent` prop has an empty fingerprint, and an empty fingerprint is what makes a parent re-render preserve the child wholesale: an untouched `data-live-preserve` stub, zero requests, state intact.
Declaring `updateFromParent: true` on any prop re-opens the parent-pushes-props channel and with it the re-render; a relation manager deliberately declares none, since its owner cannot change within a page's life (a different owner is a different URL, so a fresh mount).

## Divergences

### The two constructor arguments arrive through setters

Filament's `Livewire` takes `$component` and `$data` as `final` constructor arguments and builds through `app(static::class, [...])`.
Every Sylament component is built by [`Component::newInstance()`](component.md) through the container's component locator, which cannot pass constructor arguments, so `make()` follows the [`Flex`](flex.md#the-required-schema-arrives-through-components-not-a-constructor) shape: `newInstance()`, the two setters, then `configure()`.
The public signature is unchanged.

### The embed passes no implicit `record` prop

Filament's `getComponentProperties()` prepends `['record' => $this->getRecord()]` to the caller's data, and Livewire quietly ignores a prop the child does not declare ([`Livewire.php:93-95`](../../../vendor/filament/schemas/src/Components/Livewire.php)).
UX does not ignore it: `ComponentFactory::mountFromObject()` assigns a leftover input prop to a same-named **writable public property**, and what is still left after that becomes an *attribute* on the child's root ([`ComponentFactory.php:99-121`](../../../vendor/symfony/ux-twig-component/src/ComponentFactory.php)).
So an implicit `record` would either be swallowed by an unrelated public property or rendered, stringified, into the DOM of every embed that never asked for one.
The port passes exactly what `data()` names; a child that wants the owner takes it as a declared prop, which is what panels' relation managers do with their `ownerRecord` key string.

### The child identity is an `id` prop, and a keyless embed throws

Filament hands `LivewireFacade::mount()` a `wire:key`: `getLivewireKey()`, or `"{livewire id}.{component name}"` when the embed is keyless.
UX has no `wire:key`: a child's identity is its **`id`**, and when the mount props do not carry one it is computed as `crc32(file:line)` plus a per-render counter ([`LiveControllerAttributesCreator.php:89-95`](../../../vendor/symfony/ux-live-component/src/Util/LiveControllerAttributesCreator.php)).
Every embed on the page renders from the *same* line of the *same* partial, so that counter is render-order dependent: switching a tab would hand the newly active child the id the previously active one had, and the parent's child-preservation walk would treat it as the same component.
So the partial passes an explicit `id`, which both the attributes creator and [`InterceptChildComponentRenderSubscriber:53-57`](../../../vendor/symfony/ux-live-component/src/EventListener/InterceptChildComponentRenderSubscriber.php) prefer over the deterministic one, derived from `getLiveComponentKey()` (the bare key when there is no live host) with everything outside `[A-Za-z0-9_~=-]` replaced by `-` and prefixed `sy-live-`.

The public API stays `key()`, Filament's vocabulary, but a keyless embed is a **`LogicException`**, not a silent fallback: Filament's keyless branch keys by component name, which gives two embeds of the same child one identity, and here that is not a cosmetic collision but two live components sharing a DOM id.
The throw covers a keyless embed *under* a live host too, not only a hostless one: `getLiveComponentKey()` answers `null` on an empty key, matching [Filament's own `blank($key)` guard](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php), so `sy-live-{host id}-` can never be handed out as an id.

The sanitiser is lossy, and deliberately so: it maps every character outside `[A-Za-z0-9_~=-]` to `-`, so two keys differing only in a separator (`a.b` and `a-b`) collapse onto one id.
Keys are author-written and scoped to one host, so the collision needs two embeds keyed on purpose to differ only there, but the group arm keys an embed `"{class}-{group key}"` off a backslash-heavy class-string, which is exactly the shape where separators carry the meaning.
Reach for a hash-style disambiguator if that ever bites.

`~` and `=` are in that class **on purpose**: they are the two characters a composite [record key](record-key-contract.md#the-grammar) is built from, so without them `5~en` and `5-en` would mint one morph identity.
It matters because a relationship repeater item's key is a record key of any width, so an embed inside one is keyed `record-1~zh=20Hans` and needs every character of that class.
A collision test pins it.
Do not widen the class further: the id lands raw in an HTML attribute, and the record-key grammar is exactly what this class admits.

The cost of admitting them is a **selector** one, and it is the consuming app's rather than the toolkit's: `~` and `=` are legal in an HTML5 id but are not bare `ident` characters in CSS, so an app hand-writing `#sy-live-5~en`, in a stylesheet or in `querySelector()`, must run the id through `CSS.escape()` first.
Nothing in the toolkit does: the one consumer is this component's own partial, which interpolates the id into the `id` attribute and never selects on it.

### The host id is minted by the host, because UX has no stable component id

`getLivewireKey()` reads `getLivewire()->getId()`.
UX LiveComponent has no equivalent: the id is computed at render time by the attributes creator and is never handed to the component object.
So the schemas host contract gains `getSchemasHostId()`, implemented on [`HasSchemas`](has-schemas.md) as a `#[LiveProp] ?string $schemasHostId` filled by `bin2hex(random_bytes(4))` on first read: the dehydration read is the minting read, so the value is written into the props before it can ever be missing, and the same prefix comes back on every subsequent request.
This is a verbatim copy of tables' [`getTableHostId()`](../tables/has-table.md), which solved the identical problem for row and cell morph keys, and it is why `getLiveComponentKey()` is `{host id}.{absolute key}` rather than `{live id}.{absolute key}`.

`getLiveComponentKey()` answers `null` on both empty arms, no live host or no key, as Filament's `blank($key)` guard does.
The emptiness test is spelled `=== null || === ''` rather than `blank()` so PHPStan narrows the value at level max; the two part only over a whitespace-only key, which the key sanitizer keeps (it strips quotes, angle brackets, backticks and control characters, not spaces), so the port keys such an embed where Filament treats it as keyless.
The schema-level `HasKey` has no `getLiveComponentKey()`; [schema.md](schema.md#haskey-ports-the-schema-key-chain-minus-the-state-path-fallback) tracks it.

### The mount is a Twig frame, not a PHP call

Filament mounts inside `toEmbeddedHtml()` with the Livewire facade.
UX's [`DeterministicTwigIdCalculator`](../../../vendor/symfony/ux-live-component/src/Twig/DeterministicTwigIdCalculator.php) computes a child's id by walking the PHP backtrace for the nearest `Twig\Template` frame, so a mount from pure PHP with no Twig frame on the stack has no file and line to key on.
The embed therefore renders a one-line partial calling the `component()` Twig function, invoking a component by a runtime-computed name rather than a `<twig:>` tag as panels' `Document` partial does, for the same reason: a tag name must be a literal.

### `lazy()` is not ported, because UX's lazy defers the render, not the mount

UX ships `loading: 'lazy'|'defer'`, but it defers only the *render*: the component is still created, `mount()`ed, dehydrated and checksummed on the first paint, where Livewire's lazy skips the mount entirely.
A lazy child is also absent from the client registry until it arrives, so a dispatch aimed at it is dropped.
The one consumer, panels' relation managers under a server-driven tab bar, mounts only the active tab's managers, so the arm buys nothing.
A page whose active tab carries a heavy table would reopen this, by adopting UX's `loading: 'lazy'` rather than rebuilding Livewire's semantics.
The corollary is an invariant on the consumer rather than on this class: an embedded child must do no expensive work in `mount()`.

### There is no wrapper `div`, because the id goes on the child itself

Filament wraps the mounted HTML in a `div` carrying `getId()` and the extra attributes whenever either is set; it has to, because `wire:key` is not an attribute a caller can address.
Here the identity *is* the child's `id` attribute, so a wrapper would add a second element with a competing id for no gain.
`id()` and `extraAttributes()` on the embed reach nothing; a consumer that needs chrome around a child wraps it in a [`Group`](group.md).
