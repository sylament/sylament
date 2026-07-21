# Cross-cutting infrastructure gaps

Component parity files link these anchors instead of restating blockers. When an
item lands, grep the repo for `infrastructure.md#<anchor>` to find everything it
unblocks.

## extra-attributes

`HasExtraAttributes` + `HasId` on the component model (Filament:
`vendor/filament/support/src/Concerns/HasExtraAttributes.php`), including schema
roots accepting extra classes. Unblocks: Section's `hasContentEl` restructure
(and with it a visually functional `divided()`), id/attribute merging on
component wrapper elements.

## keys

A `key()` concept — persistence identity for components (Filament auto-slugs
Section keys from the heading in `setUp()`). Needed by collapse persistence and
LiveComponent state targeting.

## icon-system

Support-level icon rendering: a `generate_icon_html` equivalent, icon aliases,
size/color enums, an icon-button component. Unblocks: Section header icons and
the collapse chevron button.

## stimulus-controllers

Ports of Filament's Alpine behaviors to Stimulus (only `core_controller.ts`
exists today). First needed: section collapse — toggle state, id-matched window
events (`collapse-section`, `expand-section`, `open-section`, `toggle-section`),
persistence, `aria-expanded`, hide-before-init. Also the
`HasExtraAlpineAttributes` equivalent (extra Stimulus attributes on components).

## schema-slot-apis

Schema-side features that keyed slot schemas need: `inline()`,
`embeddedInParentComponent()`, alignment factories
(`Schema::start/center/end/between`), `modifyActionsUsing()` /
`modifyActionGroupsUsing()`. Unblocks: Section's `afterHeader()`, `footer()`,
`beforeLabel()`/`afterLabel()`, `aboveContent()`/`belowContent()`; Fieldset and
Wizard will need the same.

## heading-cascade

Filament's `HasHeadings`: heading tags derived from nesting depth
(`getHeadingLevel()`/`getHeadingTag()` — h2, h3, … capped to `p`), with the
Schema tracking a root heading level and components overriding
`getHeadingsCount()`.

## column-span

`columnSpan()` on components so they can size themselves inside a parent grid.
Schema-wide gap, affects every component used as a schema child.

## translation

A Symfony equivalent of Laravel's `__()` for `translateLabel()`-style APIs —
translator access from non-DI components (likely another `Invoker`/static-
instance-shaped problem).

## actions-package

The `actions` bundle itself. Unblocks: Section `headerActions()` /
`footerActions()` (+ alignment), and action support inside slot schemas.
