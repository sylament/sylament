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
| [`extraAttributes()`, replacing or merging](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`extraAttributes()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | in progress | — | [detail](#merging-the-same-array-twice-registers-it-twice) |
| [`getExtraAttributes()`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`getExtraAttributes()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | [semantics](#an-earlier-entry-wins-a-colliding-attribute-and-a-later-entrys-class-tokens-come-first) · [diverges](component-attribute-bag.md#merge-drops-the-escape-and-appendable-value-machinery-and-does-not-deduplicate) |
| [`getExtraAttributeBag()`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`getExtraAttributeBag()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | — |
| [`hasExtraAttributes()`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) | [`hasExtraAttributes()`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | — |

## Details

### Merging the same array twice registers it twice

Filament's `extraAttributes($attributes, merge: true)` returns early when `$attributes` is an array strictly equal to one already in the list ([`HasExtraAttributes.php:25`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php), `is_array(…) && in_array(…, strict: true)`), and appends every closure registration, so one closure merged twice is evaluated twice.
The port appends unconditionally ([`HasExtraAttributes.php:22-26`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php)): replacing, merging and evaluating entries all work, closure registrations match, and only the array check is missing.
With the same array merged twice, `getExtraAttributes()` folds it twice and its class tokens appear twice in the output, because the bag's `merge()` does not deduplicate tokens.
**Next step:** add the early return to `extraAttributes()` and pin a repeated-array merge.

## Semantics

### An earlier entry wins a colliding attribute, and a later entry's class tokens come first

`getExtraAttributes()` folds the entries in order, and each step merges the accumulated bag into the next entry as Laravel-style defaults.
So `data-owner` set by the first entry survives a second entry setting it again, and `class` from `['class' => 'one']` followed by `['class' => 'two three']` comes out as `['two', 'three', 'one']`.
Filament folds the same way, and both cases are pinned.
