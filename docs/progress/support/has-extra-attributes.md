---
description: The shared concern that lets a component carry extra HTML attributes for its root element, replaced or merged with `extraAttributes()`.
ports:
  - vendor/filament/support/src/Concerns/HasExtraAttributes.php
owns:
  - packages/support/src/Components/Concerns/HasExtraAttributes.php
---
# HasExtraAttributes (support)

`Sylament\Support\Components\Concerns\HasExtraAttributes` ports Filament's trait of the same name, folding its entries through support's [`ComponentAttributeBag`](component-attribute-bag.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`extraAttributes()`, replacing or merging](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`extraAttributes()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | in progress | — | [detail](#merging-the-same-closure-twice-registers-it-twice) |
| [`getExtraAttributes()`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`getExtraAttributes()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | [semantics](#an-earlier-entry-wins-a-colliding-attribute-and-a-later-entrys-class-tokens-come-first) · [diverges](component-attribute-bag.md#merge-drops-the-escape-and-appendable-value-machinery-and-does-not-deduplicate) |
| [`getExtraAttributeBag()`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`getExtraAttributeBag()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | — |
| [`hasExtraAttributes()`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`hasExtraAttributes()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | — |

## Details

### Merging the same closure twice registers it twice

Filament's `extraAttributes($attributes, merge: true)` returns early when `$attributes` is a `Closure` already in the list (`in_array(…, strict: true)`), so registering one closure instance again changes nothing.
The port appends unconditionally: replacing, merging and evaluating closure entries all work, and only that identity check is missing.
With the same closure merged twice, `getExtraAttributes()` evaluates it twice and its class tokens appear twice in the output, because the bag's `merge()` does not deduplicate tokens.

## Semantics

### An earlier entry wins a colliding attribute, and a later entry's class tokens come first

`getExtraAttributes()` folds the entries in order, and each step merges the accumulated bag into the next entry as Laravel-style defaults.
So `data-owner` set by the first entry survives a second entry setting it again, and `class` from `['class' => 'one']` followed by `['class' => 'two three']` comes out as `['two', 'three', 'one']`.
Filament folds the same way, and [`HasExtraAttributesTest`](../../../packages/support/tests/Unit/Components/Concerns/HasExtraAttributesTest.php) pins both cases.
