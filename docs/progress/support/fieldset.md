---
description: The anonymous Twig component `<twig:SylamentSupport:Fieldset>` that ports Filament's `<x-filament::fieldset>`.
ports:
  - vendor/filament/support/resources/views/components/fieldset.blade.php
  - vendor/filament/support/resources/css/components/fieldset.css
owns:
  - packages/support/assets/src/styles/components/fieldset.css
  - packages/support/templates/components/Fieldset/
---
# Fieldset (support)

`packages/support/templates/components/Fieldset/index.html.twig` is the `<fieldset>` chrome that schemas' [`Fieldset`](../schemas/fieldset.md) renders through.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`<fieldset>` root and the caller's attributes](../../../packages/support/templates/components/Fieldset/index.html.twig) | [`$attributes->class(['fi-fieldset', …])`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | done | — | — |
| [`label` → `<legend>`, omitted when blank](../../../packages/support/templates/components/Fieldset/index.html.twig) | [`@if (filled($label))`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | done | — | — |
| [`labelHidden` → `sy-fieldset-label-hidden`](../../../packages/support/templates/components/Fieldset/index.html.twig) | [`'labelHidden'`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | done | — | — |
| [`required` → `sy-fieldset-label-required-mark`](../../../packages/support/templates/components/Fieldset/index.html.twig) | [`'required'`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | done | — | — |
| [`contained` → `sy-fieldset-not-contained`](../../../packages/support/templates/components/Fieldset/index.html.twig) | [`'contained'`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | done | — | — |
| [`content` block](../../../packages/support/templates/components/Fieldset/index.html.twig) | [`$slot`](../../../vendor/filament/support/resources/views/components/fieldset.blade.php) | done | — | — |
