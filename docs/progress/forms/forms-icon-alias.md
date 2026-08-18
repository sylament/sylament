# FormsIconAlias (forms)

[`Sylament\Forms\Twig\FormsIconAlias`](../../../packages/forms/src/Twig/FormsIconAlias.php)
— the overridable icon slots the forms package exposes, ported from Filament's
[`View\FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php)
(the `View\` → `Twig\` segment is the systematic Blade → Twig translation, recorded
in [renames.md](../renames.md#namespace-segments)).

An alias is a name an application can register an icon override for under
[`sylament.icons`](../support/icon-manager.md); it does nothing until a template
passes it to `generateIconHtml()`, which is why the constant set grows with the
surface that renders the icons rather than up front.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/View/FormsIconAlias.php`](../../../vendor/filament/forms/src/View/FormsIconAlias.php)
  — the 41 alias constants and their values

## Surface

_Blocked by_ names what has to land first. _Detail_ carries either **detail** →
the write-up in [Details](#details), deleted once the row is **done**, or
**diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`COMPONENTS_CHECKBOX_LIST_SEARCH_FIELD`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| [`COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE`](../../../packages/forms/src/Twig/FormsIconAlias.php) | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | done | — | [diverges](#the-alias-values-drop-filaments-package-prefix) |
| The other 38 constants | [`FormsIconAlias`](../../../vendor/filament/forms/src/View/FormsIconAlias.php) | todo | — | [detail](#the-alias-set-is-three-constants-of-filaments-41) |

The three values are pinned by
[`FormsIconAliasTest`](../../../packages/forms/tests/Unit/Twig/FormsIconAliasTest.php),
which is what makes the de-prefixing below a decision rather than a typo. Two of
them now have a render site: `ToggleButtons`' two templates pass the boolean pair
to the `Icon` tag's `alias` prop, resolving
[at render rather than at `boolean()` call time](toggle-buttons.md#the-boolean-icons-resolve-their-alias-at-render-not-at-call-time),
and an override registered under `sylament.icons` is pinned replacing one of them
by
[`ToggleButtonsTest`](../../../packages/forms/tests/Integration/Components/ToggleButtonsTest.php).
The third has one too, since the input wrapper landed: the checkbox list's search
box passes it to the wrapper's `prefixIconAlias` prop
([the one wrapper a template opens by hand](checkbox-list.md#the-search-box-is-the-one-wrapper-a-template-opens-by-hand)),
so all three constants now resolve against real markup.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its rows flip to **done**.

### The alias set is three constants of Filament's 41

Filament declares every overridable icon slot in the whole field catalog up front.
Sylament declares the three belonging to the two option fields this epic builds.
The remaining 38 all name icons of fields that are not built here — the builder's
seven item actions, the file-upload editor's twelve toolbar buttons, the key-value
and repeater action pairs, the rich editor's panel buttons, the select's two option
actions and the text input's three affix actions.

**Next step:** none on its own. An alias is only overridable once something reads
it, so each constant lands in the same change as the field or feature that renders
its icon — the growth order
[`SupportIconAlias`](../support/support-icon-alias.md#pagination-rtl-aliases),
[`TablesIconAlias`](../tables/tables-bundle.md#the-alias-set-is-seven-constants-of-filaments-23)
and
[`NotificationsIconAlias`](../notifications/notifications-icon-alias.md#the-sixth-constant-waits-for-the-panel-that-renders-it)
all follow.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The alias values drop Filament's package prefix

Filament's values are namespaced by package —
`forms::components.checkbox-list.search-field`,
`forms::components.toggle-buttons.boolean.true`, and so on. Sylament's drop the
`forms::` segment: `components.checkbox-list.search-field`,
`components.toggle-buttons.boolean.true`,
`components.toggle-buttons.boolean.false`.

Filament is inconsistent about this — `SupportIconAlias`'s values are bare
(`modal.close-button`, `section.collapse-button`) while every other package
namespaces its own — and Sylament follows the unprefixed form throughout, so an
application registering overrides under
[`sylament.icons`](../support/icon-manager.md) reads one scheme rather than two.
The **constant** names are untouched, which is how both codebases write them at
every call site, so the difference is invisible to anyone who does not hardcode the
string. Same decision as
[`NotificationsIconAlias`](../notifications/notifications-icon-alias.md#the-alias-values-drop-filaments-package-prefix),
[`PanelsIconAlias`](../panels/README.md#layout-templates) and tables'
[`EMPTY_STATE`](../tables/tables-bundle.md#the-empty-state-alias-value-drops-filaments-package-prefix);
all four are in the [rename table](../renames.md#renames).

Only the `forms::` prefix is dropped. The `components.` segment Filament's forms
keys carry beneath it stays, so these values are one segment deeper than the bare
`<component>.<part>` form the other packages ship (`notification.danger`,
`table.empty-state`). That is the whole delta from Filament's string, and it keeps
the constant's value derivable from Filament's by deleting the prefix — nothing
else is re-spelled.
