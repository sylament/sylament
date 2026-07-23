# Filament parity trackers

Living checklists of what remains to port, per Filament component. One file per
Filament component, homed under the Sylament package that owns its public API
(`schemas/section.md` covers the schemas `Section` class *and* its support-level
Twig template/CSS — the tracking unit is the Filament component, not the file
layout).

For the epic-level view — how the whole port is sectioned into epics and in what
order they run — see [roadmap.md](roadmap.md). This file is the per-component
detail beneath it.

## Conventions

- **File**: `docs/parity/<package>/<component>.md`, kebab-case component name.
- **Sections**: `Reference` (the Filament source paths to diff against, written
  as relative markdown links — ``[`vendor/…`](../../../vendor/…)`` — so IDEs
  open them on click), `## Missing` (unchecked work only — completed items are
  deleted, git history tracks them), `## Divergences` (deliberate deviations
  from Filament, with rationale — permanent).
- **Blocked items** link an anchor in [infrastructure.md](infrastructure.md)
  instead of restating the blocker. When infrastructure lands, grep for its
  anchor to find everything it unblocks.
- **Renames** live in [renames.md](renames.md) — one global table; parity files
  link it instead of restating entries.
- **Deprecated Filament methods** (e.g. `HasLabel::disableLabel()`) are not
  ported and not tracked as missing.
- **Lifecycle**: tick nothing — delete finished items. When `## Missing` is
  empty, delete it and keep only `## Divergences`; flip the index row to done.

## Index

| Component | Package | Status |
| --- | --- | --- |
| [Component (base)](schemas/component.md) | schemas | in progress |
| [ComponentManager](support/component-manager.md) | support | in progress |
| [Field](forms/field.md) | forms | in progress |
| [Html](schemas/html.md) | schemas | done |
| [Icon button](support/icon-button.md) | support | in progress |
| [Icons](support/icons.md) | support | in progress |
| [InteractsWithSchemas](schemas/interacts-with-schemas.md) | schemas | in progress |
| [Schema](schemas/schema.md) | schemas | in progress |
| [Section](schemas/section.md) | schemas | in progress |
| [Text](schemas/text.md) | schemas | in progress |
| [ViewComponent (base)](support/view-component.md) | support | in progress |
