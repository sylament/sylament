# Filament parity trackers

Living checklists of what remains to port, per Filament component. One file per
Filament component, homed under the Sylament package that owns its public API
(`schemas/section.md` covers the schemas `Section` class *and* its support-level
Twig template/CSS — the tracking unit is the Filament component, not the file
layout).

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
| [Schema](schemas/schema.md) | schemas | in progress |
| [Section](schemas/section.md) | schemas | in progress |
