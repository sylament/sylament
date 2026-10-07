---
description: '`Sylament\Forms\Components\Concerns\HasAffixes` and its contract `Sylament\Forms\Components\Contracts\HasAffixes` — the prefix/suffix surface of the… (draft)'
ports:
  - vendor/filament/forms/src/Components/Concerns/HasAffixes.php
  - vendor/filament/forms/src/Components/Contracts/HasAffixes.php
owns:
  - packages/forms/src/Components/Concerns/HasAffixes.php
  - packages/forms/src/Components/Contracts/HasAffixes.php
---
# HasAffixes (forms)

`Sylament\Forms\Components\Concerns\HasAffixes` and its contract `Sylament\Forms\Components\Contracts\HasAffixes` — the prefix/suffix surface of the text-like controls: a label, an icon (with its own colour), and any number of actions on either side of the input, plus the inline flag that decides whether each side sits inside the input wrapper's border or beside it.
Four fields compose it — [`TextInput`](text-input.md), [`Select`](select.md), [`TagsInput`](tags-input.md) and [`DateTimePicker`](date-time-picker.md), the last passing it down to [`DatePicker`](date-picker.md) and [`TimePicker`](time-picker.md).

Three prebuilt affix actions ship against it, all [`TextInput`](text-input.md)'s — `ShowPasswordAction`, `HidePasswordAction` and `CopyAction` — and it is `cacheSuffixActions()`'s `prepareAction()` that gives `CopyAction`'s `copyMessage()` / `copyMessageDuration()` closures their `state` injection.
That state is the **field's own**, even inside a repeater item, since `prepareAction()` calls `schemaComponent()` and nothing else ([detail](text-input.md#the-copy-message-closure-sees-the-render-time-state)).

The contract extends schemas' [`HasAffixActions`](../schemas/has-actions.md#the-affix-contract-is-typed-as-a-name-keyed-map), which is what lets `Schema`'s action cache find an affix action by name without knowing anything about forms.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`prefix()` / `suffix()` / `postfix()` / `getPrefixLabel()` / `getSuffixLabel()`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [semantics](#every-setter-also-writes-the-inline-flag-so-the-last-call-wins) |
| [`prefixIcon()` / `suffixIcon()` / `getPrefixIcon()` / `getSuffixIcon()`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | — |
| [`prefixIconColor()` / `suffixIconColor()` / `getPrefixIconColor()` / `getSuffixIconColor()`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [semantics](#the-icon-colour-defaults-nowhere-in-php) |
| [`prefixAction()` / `prefixActions()` / `suffixAction()` / `suffixActions()`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [semantics](#the-plural-forms-append-and-a-closure-may-return-several-actions) |
| [`getPrefixActions()` / `cachePrefixActions()` / `getSuffixActions()` / `cacheSuffixActions()`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [semantics](#the-cache-contributes-two-defaults-and-nothing-else) |
| [`inlinePrefix()` / `inlineSuffix()` / `isPrefixInline()` / `isSuffixInline()`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [semantics](#every-setter-also-writes-the-inline-flag-so-the-last-call-wins) |
| [The contract](../../../packages/forms/src/Components/Contracts/HasAffixes.php) — the eight getters, `extends HasAffixActions` | [`Components/Contracts/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Contracts/HasAffixes.php) | done | — | [diverges](#htmlable-becomes-markup-in-the-contract-too) |

Coverage: [`HasAffixesTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasAffixesTest.php) — the literal, `Markup` and closure arms of every label, icon and colour; the inline-flag trap; append semantics across the singular and plural forms; a closure returning an array and a closure returning `null`; the name keying, the two cached defaults, `prepareAction()`'s schema component, and the splice into `getActions()`.

## Semantics

### Every setter also writes the inline flag, so the last call wins

`prefix()`, `suffix()`, `prefixIcon()`, `suffixIcon()` and the four action setters each take a second `bool|Closure $isInline` parameter defaulting to `false`, and each writes it through `inlinePrefix()` / `inlineSuffix()` before returning.
There is one flag per side, so the *last* affix call on that side decides it:

```php
TextInput::make('weight')->inlinePrefix()->prefix('kg')->isPrefixInline(); // false
TextInput::make('weight')->prefix('kg', isInline: true)->isPrefixInline();  // true
```

This is Filament's behaviour, kept deliberately — the two ways to say the same thing are part of the API migrating users have in their fingers, and the override direction is the one a fluent chain reads left to right.
The cost is that a standalone `inlinePrefix()` is only safe as the last word on its side.

### The plural forms append, and a closure may return several actions

`prefixActions()` / `suffixActions()` spread onto the existing array rather than replacing it, and the singular forms are one-element calls into them — so `->prefixAction(a)->prefixActions([b])` holds both.
Each entry may be an `Action` or a closure, and the closure's result goes through [`wrap()`](../support/helpers.md), so it may return one action, a list of them, or `null` (contributing nothing).
Filament reaches `Arr::wrap` here; the port's helper has the same `null` → `[]` arm.

### The cache contributes two defaults and nothing else

`cachePrefixActions()` / `cacheSuffixActions()` key the prepared actions **by the action's own name**, which is what the schemas splice relies on — the [`cacheActions()`](../schemas/has-actions.md) merge spreads the maps, and integer keys would be renumbered and lose the lookup a mounted action needs.
A later action of the same name replaces an earlier one.

Each action is given exactly two defaults on the way in — `defaultSize(Size::Small)` and `defaultTemplate(Action::ICON_BUTTON_TEMPLATE)` (Filament's `defaultView(Action::ICON_BUTTON_VIEW)`, translated) — and then `prepareAction()`, which sets the schema component.
Both are *defaults*, so an explicit `size()` or `link()` on the action still wins.
No colour is contributed: an action wanting one sets it itself.

The cache is filled on first read and reused; a setter called afterwards does not invalidate it, so a consumer adding actions late calls `cachePrefixActions()` to rebuild.
Filament is identical.

### The icon colour defaults nowhere in PHP

`getPrefixIconColor()` / `getSuffixIconColor()` return `null` when unset — the `'gray'` default lives at the call site in the templates, exactly as Filament's Blade does.
Reading them in PHP therefore tells you what the caller asked for, not what renders.

## Divergences

### `Htmlable` becomes `Markup` in the contract too

The house translation: wherever Filament types a human-text or icon value `string | Htmlable`, the port types it `string|Markup` — plain strings are escaped by the template, `Markup` renders raw.
It applies to the labels and the icons in both the trait's properties and the contract's return types.
The icon setters also carry the `BackedEnum` arm every house icon setter has ([`HasIcon`](../support/has-icon.md), [`HasToggleIcons`](toggle.md#hastoggleicons-tests-for-null-not-truthiness)), matching the vendor trait.
