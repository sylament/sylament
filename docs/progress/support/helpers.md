---
description: The `Sylament\Support` namespaced global functions, porting Filament's helpers and macros and the Laravel helpers Filament's code calls.
ports:
  - vendor/filament/support/src/helpers.php
  - vendor/filament/support/src/SupportServiceProvider.php
owns:
  - packages/support/src/Twig/Extension/IconExtension.php
  - packages/support/src/Twig/Extension/MarkupExtension.php
  - packages/support/src/helpers.php
---
# helpers.php (support)

[`packages/support/src/helpers.php`](../../../packages/support/src/helpers.php) holds the `Sylament\Support`-namespaced global functions (composer `files` autoload, imported with `use function`).
It ports Filament's own `helpers.php`, two `Str` macros Filament declares in its service provider, and the Laravel global helpers Filament's code leans on but never defines (`blank()`, `filled()`, `Arr::wrap()`, `Arr::only()`, `Arr::except()`, `Str::plural()`), reimplemented because Sylament has no Laravel runtime.
Every function in it is pure, and that is an invariant (see [the divergence below](#generate_icon_html-moved-to-the-twig-extension)).
[`MarkupExtension`](../../../packages/support/src/Twig/Extension/MarkupExtension.php) is the Twig face of the file: its `filled` test is `filled()`, so a template reads blankness the way PHP does.
The markup of Filament's `generate_loading_indicator_html()` is ported as the [loading indicator](loading-indicator.md) component, which tracks the rest.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`blank()`](../../../packages/support/src/helpers.php) | [`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php) | done | — | [diverges](#blank-gives-markup-text-semantics-despite-its-countable-count) |
| [`filled()`](../../../packages/support/src/helpers.php) | [`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php) | done | — | — |
| [`wrap()`](../../../packages/support/src/helpers.php) | [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| [`sanitize_url()`](../../../packages/support/src/helpers.php) | [`Str::sanitizeUrl` in `SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [diverges](#sanitize_url-is-a-helper-because-the-macro-target-does-not-exist-here) |
| [`sanitize_css_color()`](../../../packages/support/src/helpers.php) | [`Str::sanitizeCssColor` in `SupportServiceProvider.php`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [diverges](#sanitize_css_color-is-the-second-macro-that-lands-as-a-helper) |
| [`diff_for_humans()`](../../../packages/support/src/helpers.php) | [`CarbonInterval::forHumans()`](../../../vendor/nesbot/carbon/src/Carbon/CarbonInterval.php) at `$parts = 1` | done | — | [diverges](#diff_for_humans-is-a-carbon-port-not-a-carbon-dependency) |
| [`get_record_label()`](../../../packages/support/src/helpers.php) | [`get_model_label()`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#get_record_label-is-a-rename-and-it-is-what-put-symfonystring-in-supports-manifest) |
| [`only()`](../../../packages/support/src/helpers.php) | [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| [`except()`](../../../packages/support/src/helpers.php) | [`Illuminate/Collections/Arr.php`](../../../vendor/laravel/framework/src/Illuminate/Collections/Arr.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| [`plural()`](../../../packages/support/src/helpers.php) | [`Str::plural()`](../../../vendor/laravel/framework/src/Illuminate/Support/Str.php) | done | — | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| [`markup()` function, `filled` and `markup` tests](../../../packages/support/src/Twig/Extension/MarkupExtension.php) | — (Sylament addition, the Twig face of `filled()` and `Twig\Markup`) | done | — | — |
| `generate_icon_html()`, on [`IconExtension`](../../../packages/support/src/Twig/Extension/IconExtension.php) | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#generate_icon_html-moved-to-the-twig-extension) · [diverges](#decorative-icon-defaults-rely-on-ux-icons-not-on-bundled-icon-sets) |
| `get_component_color_classes()`, as [`colorAttributes()`](../../../packages/support/src/Twig/Extension/ColorExtension.php) | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | done | — | [diverges](#get_component_color_classes-is-the-string-arm-of-colorattributes) |
| `format_money()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | skipped | — | [Number::currency()](#format_money-and-format_number-are-deprecated-so-they-are-skipped) |
| `format_number()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | skipped | — | [Number::format()](#format_money-and-format_number-are-deprecated-so-they-are-skipped) |
| `generate_search_column_expression()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | skipped | — | [search-constraint.md](../schemas/search-constraint.md) |
| `generate_search_term_expression()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | skipped | — | [search-constraint.md](../schemas/search-constraint.md) |
| `original_request()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | skipped | — | [resource.md](../panels/resource.md#url-generation-inside-a-live-action-resolves-the-panel-the-page-was-opened-in) |
| `locale_has_pluralization()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | blocked | toolkit i18n | [diverges](#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded) |
| `prepare_inherited_attributes()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `is_slot_empty()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | [detail](#is_slot_empty-has-no-twig-counterpart-to-wrap) |
| `is_app_url()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `generate_href_html()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | [tabs.md](tabs.md#the-anchor-arm-emits-href-and-target-directly-instead-of-generate_href_html) |
| `discover_app_classes()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `get_composer_vendor_directory()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `is_path_within_directory()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `is_path_within_vendor_directory()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | — |
| `get_color_css_variables()` | [`helpers.php`](../../../vendor/filament/support/src/helpers.php) | todo | — | [color-manager.md](color-manager.md#the-six-shade-mutation-methods-are-not-ported) |

## Details

### `is_slot_empty()` has no Twig counterpart to wrap

Filament's `is_slot_empty()` wraps any `Htmlable` in a Blade `ComponentSlot` and asks `hasActualContent()`, which ignores comments and whitespace.
Twig has no slot object with that method, so the port needs its own emptiness test over rendered markup, and it lands with the first template that has to tell an empty block from a filled one.

## Divergences

### `generate_icon_html()` moved to the Twig extension

Filament defines it as a global function that reaches the icon registry through the `FilamentIcon` facade.
A free function has no `$this`, so ported literally it would make [`IconManager`](icon-manager.md) a static instance for that one reason.
Its only `src/` caller is [`IconExtension`](../../../packages/support/src/Twig/Extension/IconExtension.php), a service, so the body lives there with the manager constructor-injected, and there is no free function.
`IconManager` is a plain service.

Nothing consumer-facing changes: templates call the Twig function `generateIconHtml`, with Filament's name and arguments.
Only a PHP caller of a free `Sylament\Support\generate_icon_html()` would notice its absence.
Registering icon aliases is service-only (`$container->get(IconManager::class)->register([...])`), which is what a Symfony app reaches for anyway.

This keeps every function in `helpers.php` pure.
Keep it that way: a helper that needs a service belongs on the service, as `get_component_color_classes()` does on `ColorExtension`, rather than becoming a second static instance.

### `get_component_color_classes()` is the string arm of `colorAttributes()`

Filament's `get_component_color_classes()` returns `[]` for a blank colour and otherwise asks the `FilamentColor` facade for `getComponentClasses()`; its callers are the toggle field, the toggle column and the toggle Blade view.
The port's toggle templates call [`colorAttributes()`](../../../packages/support/src/Twig/Extension/ColorExtension.php) with the `ToggleComponent` policy instead (`forms/templates/partials/Toggle/index.html.twig:2-3`), whose string arm does the same blank check and the same `ColorManager::getComponentClasses()` call, returning the classes in a `{class, style}` pair.
A free function would need the manager from a global, which [the rule above](#generate_icon_html-moved-to-the-twig-extension) rules out.

### `format_money()` and `format_number()` are deprecated, so they are skipped

Filament marks both `@deprecated`, pointing at `Illuminate\Support\Number::currency()` and `Number::format()` respectively.
The port ships no deprecated surface, and `Number` has no port: a consumer needing either formatting ports the replacement, not these.

### Decorative-icon defaults rely on ux-icons, not on bundled icon sets

An unnamed icon must end up decorative on **both** render branches, and only one of those branches is ours: the image branch, where `generateIconHtml()` merges `alt=""` unless the caller set `alt`, `aria-label`, or `aria-labelledby`.
The SVG branch is not: Filament gets its `aria-hidden` from the icon sets it ships, while Sylament renders through [`IconManager`](icon-manager.md) onto ux-icons, which adds `aria-hidden` itself at render time when no textual alternative is present ([`IconRenderer`](../../../vendor/symfony/ux-icons/src/IconRenderer.php)).
Same outcome, different owner: swapping the icon renderer would silently take half of this guarantee with it, so a replacement has to be checked for that behaviour.

### `blank()` gives `Markup` text semantics despite its `Countable` count

Laravel checks `Countable` before stringifying, so a whitespace-only value that is also `Countable` counts by element and reports **filled**.
`Twig\Markup` is `Countable` (it counts its characters), so under Laravel's order a `Markup('   ')` would be filled.
Sylament special-cases `Markup` **before** the `Countable` branch and trims its string form, so it keeps the text semantics Laravel's non-`Countable` `HtmlString` gets: a whitespace-only `Markup` is blank.
Other `Countable + Stringable` objects (Doctrine collections) keep Laravel's count-first semantics.

### `blank()` trims with `mb_trim`, so unicode whitespace counts as blank

Laravel's `blank()` trims with byte `trim()`, so an NBSP- or em-space-only string is filled.
Sylament trims with `mb_trim` (PHP 8.4+; the package requires `php: ^8.5`), so `"\u{00A0}\u{2007}"` and other unicode-whitespace-only strings count as blank, matching the "no visible content" reading a UI toolkit wants.

### No `Model`-to-filled short-circuit

Laravel's `blank()` short-circuits `instanceof Model → filled` before the `Countable` check.
There is no Doctrine equivalent, so Sylament omits it: a `Countable`-empty entity reports **blank** rather than being forced filled by an entity-type check.
The `Htmlable → Twig\Markup` and `Eloquent → Doctrine` translations are in [translations.md](../translations.md).

### The `@phpstan-assert` tags model these as null-checks only

`blank()` and `filled()` carry `@phpstan-assert-if-false !null $value` and `@phpstan-assert-if-true !null $value`, which Laravel's originals do not; the tags let a caller narrow a `mixed` past `null` without a second check.
The cost is that PHPStan reads the tag as the **whole** contract: to the analyser `blank()` answers true for `null` and nothing else, though the body also answers true for `''`, `'   '`, `[]` and an empty `Countable`.
So a call on a value already narrowed past `null` is *impossible* (`Call to function Sylament\Support\blank() with bool|float|int|string will always evaluate to false`, identifier `function.impossibleType`), and at level max that is an error, not a hint.

The rule that follows: **test `blank()` before any other narrowing, never after.**
`!is_scalar($key) || blank($key)` is red where `blank($key) || !is_scalar($key)` is green, and the two agree at runtime because `blank()` already accepts any type.
The errors surface only where an analysed class composes the trait holding the call ([has-table.md](../tables/has-table.md#a-host-trait-is-analysed-only-through-a-class-that-composes-it)).
A helper call PHPStan has proved constant is a signal to reorder the guard (or to delete the defensiveness that made the call look necessary), not to reach for a suppression.

### `sanitize_url()` is a helper because the macro target does not exist here

Filament declares it as a `Str::macro` (and a `Stringable::macro` forwarding to it) in [`SupportServiceProvider::packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php), so it is neither a `helpers.php` function nor a class method.
Macro-based extension is a rejected pattern here ([why](component.md#macroable-is-not-ported)) and there is no `Str` to extend, so the body lands in this file: it is pure and takes only its arguments, and its consumers (forms' `BaseFileUpload`, support's `FilesystemRegistry`, tables' record URLs and `ImageColumn`'s default image URL, which also allows `data`) import it with `use function` because each embeds the result in an `href` or `src`.
The `Stringable` forwarder has no analog and is dropped.

The body otherwise follows Filament's step for step, and keeps the comments Filament carries, lightly reworded: they state *why* each of the four refusals exists (which obfuscation each one catches), which the code cannot.
Three mechanical adjustments: `preg_match()` results are compared `=== 1` rather than used as booleans, `strtolower()` becomes `mb_strtolower()` to match the rest of the codebase (schemes matched by the regex are ASCII, so the two agree), and the entity callback is a `static` closure that casts `hexdec()` before comparing.
One behavioural addition: a `null` from `preg_replace_callback()`, a PCRE failure, is a **refusal** rather than a crash, because a sanitizer that cannot decode its input must not pass it through.

### `get_record_label()` is a rename, and it is what put `symfony/string` in support's manifest

The name follows the toolkit-wide [collapse of the model vocabulary onto record](../renames.md#the-model-vocabulary-collapses-onto-record); the body is Filament's, translated helper for helper: `str($model)->classBasename()->kebab()->replace('-', ' ')` becomes `u($class)->afterLast('\\')->kebab()->replace('-', ' ')`.
The `class-string<Model>` parameter type loses its bound with Eloquent: any class-string is a record class here, and the function never loads one.
Filament memoises each label in a `static $modelLabels` array; the port recomputes it on every call.

It has to be `kebab()` rather than a regex.
`panels`' `Resource::getRecordLabel()` and the actions' record label must agree byte for byte, or a resource page and a modal action would disagree about what the record is called, and `AbstractUnicodeString::kebab()`'s acronym handling (`PDFDocument` → `pdf document`, not `p-d-f document`) is not something a hand-rolled expression reproduces by accident.
`Resource::getRecordLabel()` delegates here, leaving one definition.

The consequence is a manifest line: support requires `symfony/string`, for this function and for [`plural()`](../../../packages/support/src/helpers.php), its second consumer through `EnglishInflector`.
It is the placement-by-source rule doing its job: this is Filament-**support**'s helper, and both `actions` and `panels` read it, so it cannot move up into either; the constraint is the `^8.1` the other packages declaring it carry, under the blanket `symfony/*` approval.

### `wrap()` and the Laravel globals are reimplemented, not forwarded

`blank()`, `filled()`, `wrap()`, `only()`, `except()` and `plural()` are **not** Filament `helpers.php` functions: they are Laravel globals (`blank()` / `filled()`) and `Arr::wrap()` / `Arr::only()` / `Arr::except()` / `Str::plural()` that Filament's code consumes without defining, `blank()`/`filled()` in `helpers.php` itself and the three `Arr` methods across the packages this port reads (`filament/support` alone calls all three: `Arr::only` in `Assets\AssetManager`, `Arr::except` in `Services\RelationshipJoiner`, `Arr::wrap` in `Icons\IconManager` and the view classes).
Because Sylament ships no Laravel runtime, they are reimplemented here in the `Sylament\Support` namespace so the ported Filament helpers and every downstream component have them.
`wrap()` is a free-function port of the static `Arr::wrap()` method, kept minimal (the `null → []`, array-passthrough, scalar-to-single-element contract), with no `Collection` or `Arrayable` handling, because nothing in the port needs it.
Its main job is guarding the `foreach (wrap($this->evaluate(…)))` shape the schemas and actions concerns use: a closure that returns `null` or a single item would otherwise silently empty the loop or fatal it.

`only()` and `except()` are the same trade one layer up: one-line `array_intersect_key()` / `array_diff_key()` bodies over `array_flip($keys)`, without the `Collection`, `Arrayable` or dotted-path arms Laravel's methods carry, because none of their consumers asks for them: `CreateAction`'s `preserveFormDataWhenCreatingAnother()` keeps a flat list of field names, while `ReplicateAction`'s `excludeAttributes()`, actions' `CanMutateRecordData` and forms' `CanFixIndistinctState` drop flat keys.

`plural()` is that trade a third time, and the one whose body is not a one-liner: Laravel's `Str::plural` runs the **last word** of a phrase through an inflector and leaves the rest alone, so `blog post` becomes `blog posts`, the inflector seeing only `post`.
symfony/string's `EnglishInflector` is the inflector; `pluralize()` returns *candidates* rather than one answer (`person` → `['persons', 'people']`), and the port takes the last.
`panels`' `Resource` and the actions' plural labels call this function rather than carrying their own inflection, because two implementations would drift.
Laravel's `$count`/`$prependCount` arms are not ported (nothing asks), and neither is its `locale_has_pluralization()` guard: i18n is deferred, so the port pluralises English unconditionally.

### `sanitize_css_color()` is the second macro that lands as a helper

Filament declares it as a `Str::macro` beside `sanitizeUrl` in [`SupportServiceProvider::packageBooted()`](../../../vendor/filament/support/src/SupportServiceProvider.php), so it lands here for the same reason [`sanitize_url()` does](#sanitize_url-is-a-helper-because-the-macro-target-does-not-exist-here): it is pure, it takes only its argument, and there is no `Str` to extend.
The three accept branches are verbatim (hex `#rgb`/`#rgba`/`#rrggbb`/`#rrggbbaa`, a bare `[a-z]+` keyword, and the ten functional notations `rgb`, `rgba`, `hsl`, `hsla`, `hwb`, `lab`, `lch`, `oklab`, `oklch`, `color` with `( ) ; : " '` forbidden between the parentheses), as is the `// Security:` comment stating which injection that last refusal exists to stop.
Three mechanical adjustments: the `BackedEnum` arm is dropped with the parameter typed `?string` (nothing in the port passes an enum, and a caller with one unwraps it at the call site), `preg_match()` results are compared `=== 1`, and `trim()` becomes `mb_trim()` for the reason [`blank()` uses it](#blank-trims-with-mb_trim-so-unicode-whitespace-counts-as-blank): a value padded with unicode whitespace (`"\u{00A0}red"`) is accepted here and refused upstream, and the accepted answer is the trimmed string, so the padding never reaches the declaration either way.

**It is not `ColorManager::sanitizeCssColor()`**, which stays.
That one answers a different question ("is this whole string safe to interpolate into a `--sy-*` custom property?") with one permissive character-class regex, and returns `''` rather than `null` for a refusal, because a dropped declaration there must not become a syntax error in the emitted `<style>` block ([color-manager.md](color-manager.md#the-css-colour-sanitizer-is-a-method-on-the-manager-and-drops-rather-than-nulls)).
This one asks "is this a CSS colour?" and answers `null`, so its consumer, tables' `ColorColumn`, can render an unnamed, uncoloured swatch instead.

### `diff_for_humans()` is a Carbon port, not a Carbon dependency

`nesbot/carbon` is not adopted: the one call the toolkit needs is `diffForHumans()` at its `$parts = 1` default, and its algorithm is forty lines over `DateTimeInterface::diff()`.
The port walks `year → month → week → day → hour → minute → second`, taking the **first non-zero** unit and stopping (weeks are `intdiv($d, 7)` and days `$d % 7`, which is how Carbon derives its `weeks` / `daysExcludeWeeks` pair in [`CarbonInterval::forHumans()`](../../../vendor/nesbot/carbon/src/Carbon/CarbonInterval.php)) and pluralises the count in English.
There are no thresholds and no rounding, which is Carbon's behaviour too: `47 seconds ago`, never "a minute ago".
An all-zero difference falls back to `0 seconds`.

One consequence of taking the **first** non-zero unit is that the `day` arm's `% 7` is unobservable: dropping it (`'day' => $d`) changes no answer, because the walk only reaches `day` when `intdiv($d, 7)` is zero and `$d` is therefore already under seven.
It is written the Carbon way anyway, because the modulo is what makes the arm read as *days beyond the weeks*, which is what the next reader needs the moment `$parts` grows past one, so a surviving mutant there is an equivalent one, not a coverage hole.

Two deliberate narrowings.
Carbon's `$syntax`, `$short`, `$parts` and `$options` arguments have no port: the behaviours they select (absolute phrasing, `before`/`after` instead of `ago`/`from now`, short units, multi-part output, `a_second`-style rounding, `JUST_NOW`) have no consumer, and adding one is additive.
And the English strings are literals rather than a translation catalogue, i18n being deferred.

The `$now` argument is required rather than defaulted to the current time, so the function stays pure and testable: the consuming column passes `new DateTimeImmutable('now', $timezone)`.
The reading is `$date->diff($now)`, Carbon's own operand order ([`Difference.php`](../../../vendor/nesbot/carbon/src/Carbon/Traits/Difference.php) `$this->diff($other)`), so `invert === 1` means the date is in the future and reads `from now`, while everything at or before `$now`, the zero difference included, reads `ago`, exactly as Carbon does.
