# Support enablers (slice subset) — task plan

> Task breakdown for the epic designed in
> [support-enablers.md](support-enablers.md). The Design, Decisions,
> Divergences, and Non-goals in that file are **settled** — these briefs execute
> it, they do not re-open it. Each brief is self-contained for a cold session:
> goal, the vendor source to port from, the exact current-`main` files to touch,
> the locked decisions, and acceptance criteria. Honor the
> [Shared conventions](#shared-conventions) once; briefs don't restate them.

## Sequencing

The two tasks are **independent** — no shared code, no ordering dependency
(S1 is `Enum/` + `Component/Concerns/`; S2 is `Twig/` + templates). Do S1 first
only because it is the smaller warm-up.

```
S1 enums + paired traits      (Enum/, Component/Concerns/)
S2 color() helper + refactor  (Twig/, templates/)   — independent of S1
```

## Shared conventions

Every task satisfies these (from [CLAUDE.md](../../CLAUDE.md)); not repeated per
brief.

- **DoD gates:** `composer test` green (Pest + Rector dry-run + PHPStan **level
  max**) and `pnpm test:types` + `pnpm test:lint` green. Run `composer format`
  (Rector + PHP-CS-Fixer) — **never hand-format** (the code blocks below are
  spaced for readability; formatting is authoritative). No PHPStan baseline /
  ignore (`reportUnmatchedIgnoredErrors` is on).
- **Reference Filament first:** `vendor/filament/support/` is the source of
  truth; preserve public API names, translate Laravel→Symfony idiom.
- **Closure typing:** setters take native `Closure` unions, direct assignment,
  no param PHPDoc, no arity specs. Props carry native types only; `evaluate()`
  (from `EvaluatesClosures`) supplies the generics.
- **Test layout:** Pest; mirror `src/` under `tests/Unit` (no kernel).
  `Fixtures/` mirrors `src/`. Inside `it()`/`test()` reach static helpers via
  `$this::`, never `self::`/`static::`. The Unit Pest hook in
  [`packages/support/tests/Pest.php`](../../packages/support/tests/Pest.php)
  already installs the `Invoker` static instance (so `evaluate()` works in trait
  Unit tests) — don't re-add it per file. Add fixtures by dropping classes under
  `tests/Fixtures/…`, never by editing the Kernel.
- **Parity docs in the same change:** record the design's
  [Divergences](support-enablers.md#divergences-record-when-code-lands) under
  [infrastructure.md#support-managers](infrastructure.md#support-managers); flip
  the [roadmap.md](roadmap.md) Milestone 1 / step-1 row as the slice lands. No
  per-component parity file (enums/traits are faithful; the color note is a
  thread note — design §Divergences).
- **Self-review pass** (>~30 lines / >2 files): run static analysis + tests,
  re-read each modified file end-to-end, grep callers of any changed signature,
  report findings before declaring done.

---

## S1 — Enums + paired traits

- [ ] **S1** — the `Operation`/`Width`/`VerticalAlignment` enums and the
  `HasWidth`/`HasVerticalAlignment` traits, deterministic, Pest Unit-verified
  (traits only — bare enums are untested by convention).

**Goal.** Add the three slice-subset enums under `Sylament\Support\Enum` and the
two paired support-component traits under `Sylament\Support\Component\Concerns`,
verbatim from Filament under Sylament conventions. Nothing consumes them yet —
they are enablers the slice's later epics (`HasWidth`→Modal, `HasVerticalAlignment`
→layout/tables, `Operation`→record-binding/panels) `use`.

**Port from.**
- `vendor/filament/support/src/Enums/Operation.php`,
  `vendor/filament/support/src/Enums/VerticalAlignment.php`,
  `vendor/filament/support/src/Enums/Width.php` — bare `string`-backed enums,
  cases copied verbatim.
- `vendor/filament/support/src/Concerns/HasWidth.php` (the full trait:
  `int | string | Closure | null $width`, `width()`, `getWidth()` int→`"{n}px"`)
  and `vendor/filament/support/src/Concerns/HasVerticalAlignment.php` (`verticalAlignment()`,
  `verticallyAlignStart/Center/End()`, `getVerticalAlignment()` with `tryFrom`,
  `hasDynamicVerticalAlignment()`).
- **In-repo templates to mirror exactly:** the existing enum
  [`Alignment.php`](../../packages/support/src/Enum/Alignment.php) (bare-enum
  shape) and the trait [`HasAlignment.php`](../../packages/support/src/Component/Concerns/HasAlignment.php)
  + its fixture/test
  ([`HasAlignmentFixture.php`](../../packages/support/tests/Fixtures/Component/Concerns/HasAlignmentFixture.php),
  [`HasAlignmentTest.php`](../../packages/support/tests/Unit/Component/Concerns/HasAlignmentTest.php))
  — `HasVerticalAlignment` is structurally identical to `HasAlignment`
  (enum-typed, `tryFrom`-normalizing, condition-wrapping sugar setters), so copy
  its fixture/test shape.

**Touch (current `main`) — new files.**

- `packages/support/src/Enum/Operation.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Support\Enum;

enum Operation: string
{
    case Create = 'create';
    case Edit = 'edit';
    case View = 'view';
}
```

- `packages/support/src/Enum/VerticalAlignment.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Support\Enum;

enum VerticalAlignment: string
{
    case Start = 'start';
    case Center = 'center';
    case End = 'end';
}
```

- `packages/support/src/Enum/Width.php` — all 26 cases verbatim from Filament
  (`ThreeExtraSmall = '3xs'`, `TwoExtraSmall = '2xs'`, `ExtraSmall = 'xs'`,
  `Small = 'sm'`, `Medium = 'md'`, `Large = 'lg'`, `ExtraLarge = 'xl'`,
  `TwoExtraLarge = '2xl'`, `ThreeExtraLarge = '3xl'`, `FourExtraLarge = '4xl'`,
  `FiveExtraLarge = '5xl'`, `SixExtraLarge = '6xl'`, `SevenExtraLarge = '7xl'`,
  `None = 'none'`, `Full = 'full'`, `MinContent = 'min'`, `MaxContent = 'max'`,
  `FitContent = 'fit'`, `Prose = 'prose'`, `Container = 'container'`,
  `ScreenSmall = 'screen-sm'`, `ScreenMedium = 'screen-md'`,
  `ScreenLarge = 'screen-lg'`, `ScreenExtraLarge = 'screen-xl'`,
  `ScreenTwoExtraLarge = 'screen-2xl'`, `Screen = 'screen'`), `namespace
  Sylament\Support\Enum`, `declare(strict_types=1)`.

- `packages/support/src/Component/Concerns/HasWidth.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Support\Component\Concerns;

use Closure;

trait HasWidth
{
    protected int|string|Closure|null $width = null;

    public function width(int|string|Closure|null $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): ?string
    {
        $width = $this->evaluate($this->width);

        if (is_int($width)) {
            $width = "{$width}px";
        }

        return $width;
    }
}
```

  ⚠️ The union is `int|string|Closure|null` — **not** typed against the `Width`
  enum (Filament-exact: the base trait is a generic width holder; consumers that
  take the enum pass `Width::Large->value` or widen the union themselves). Do not
  widen it here, or `getWidth(): ?string` stops being type-sound.

- `packages/support/src/Component/Concerns/HasVerticalAlignment.php` — port
  Filament's trait verbatim, `namespace Sylament\Support\Component\Concerns`,
  `use Sylament\Support\Enum\VerticalAlignment;`: the `verticalAlignment(...)`
  setter, the three `verticallyAlign{Start,Center,End}(bool|Closure $condition =
  true)` sugar setters (each `return $this->verticalAlignment(fn (): ?VerticalAlignment
  => $this->evaluate($condition) ? VerticalAlignment::X : null);`),
  `getVerticalAlignment(): VerticalAlignment|string|null` (`$v = $this->evaluate(...)`;
  if not a string return it; else `VerticalAlignment::tryFrom($v) ?? $v`), and
  `hasDynamicVerticalAlignment(): bool` (`$this->verticalAlignment instanceof
  Closure`).

  ⚠️ **Do not** add a `getVerticalAlignmentClass()` helper. Sylament's
  `HasAlignment` adds `getAlignmentClass()` (`sy-align-<value>`) beyond Filament,
  but that class fragment has CSS + a consumer; vertical alignment has neither
  this epic. The layout/table epic that wires this trait names its class fragment
  then (record the deferral in the roadmap note, not here).

**Touch (current `main`) — new tests/fixtures.**

- `packages/support/tests/Fixtures/Component/Concerns/HasWidthFixture.php` and
  `HasVerticalAlignmentFixture.php` — mirror `HasAlignmentFixture` exactly:

```php
<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\HasWidth;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasWidthFixture
{
    use EvaluatesClosures;
    use HasWidth;
}
```

  (and the analogous `HasVerticalAlignmentFixture` with `use HasVerticalAlignment;`).

- `packages/support/tests/Unit/Component/Concerns/HasWidthTest.php` — cases:
  default `getWidth()` is `null`; `->width('md')->getWidth()` is `'md'`;
  `->width(200)->getWidth()` is `'200px'`; a closure resolves
  (`->width(fn (): string => 'lg')` ⇒ `'lg'`, `->width(fn (): int => 320)` ⇒
  `'320px'`); last call wins (`->width('sm')->width('lg')` ⇒ `'lg'`).
- `packages/support/tests/Unit/Component/Concerns/HasVerticalAlignmentTest.php` —
  copy `HasAlignmentTest`'s cases minus the three `getAlignmentClass` cases:
  default null; stores a `VerticalAlignment` case; normalizes `'center'` ⇒
  `VerticalAlignment::Center`; passes an unknown string (`'custom'`) through;
  evaluates a closure (enum and string forms); each
  `verticallyAlign{Start,Center,End}()` stores its case; a `false` condition ⇒
  null; a condition closure resolves; last call wins;
  `hasDynamicVerticalAlignment()` is false by default, false for a stored case,
  true for a stored closure, true after a sugar helper (the helpers always store
  a condition-wrapping closure).

**Do not add enum tests** — bare enums are untested by convention (no
`tests/Unit/Enum/` dir exists; `Size`/`Alignment`/etc. have none).

**Locked decisions.** Enums/traits defined, not wired (design §3; relies on the
global `trait.unused` ignore). `Operation` = enum only (§4). Full `Width`/`VerticalAlignment`
enums (§5). `HasWidth` union stays `int|string|Closure|null` (Filament-exact).

**Acceptance criteria.** The five source files + two fixtures + two Unit tests
exist; `composer test:support` green (new trait tests pass, PHPStan max clean —
note the traits are `use`d only by fixtures, which satisfies `trait.unused`
locally anyway). `composer test` green. `pnpm` gates no-op but green. New
classes under `Sylament\Support\` need no composer step (autoload covers them).

**Notes / pitfalls.** `getWidth()` returns `?string`; keep the union off the
`Width` enum (above). Match `HasAlignment`'s exact spacing/ordering after
`composer format`. The `verticallyAlign*` helpers store closures even for literal
conditions — so `hasDynamicVerticalAlignment()` is `true` after them (mirror
`HasAlignmentTest`'s note).

---

## S2 — `color()` helper + centralize call sites

- [ ] **S2** — a shared `color()` attribute helper that lifts the hand-rolled
  `sy-color-<name>` derivation out of four templates into one place,
  output-equivalent (existing render tests are the gate).

**Goal.** Add `ComponentAttributeBag::colorClasses()` (pure logic) + a `color()`
Twig function, and refactor the four hand-rolled call sites to use it. The
rendered classes stay byte-identical; this is a mechanism change (design §6).

**Port from.**
- `vendor/filament/support/src/View/ComponentAttributeBag.php:57` (the `color()`
  method: classes for a named palette, styles for arbitrary — Sylament takes the
  **classes** half only; the styles/array half is the deferred `ColorManager`
  seam) and `vendor/filament/support/src/SupportServiceProvider.php:210` (macro
  registration).
- **In-repo:** the existing `attrs()`/`classes()` split in
  [`AttributeExtension.php`](../../packages/support/src/Twig/Extension/AttributeExtension.php)
  (`classes()` → `getClassTokens()` → `ComponentAttributeBag::toTokenList()`);
  mirror it for `color()` → `getColorClasses()` → `ComponentAttributeBag::colorClasses()`.
- The four call sites' current logic (design §"Current graft surface").

**Touch (current `main`).**

- [`packages/support/src/Twig/ComponentAttributeBag.php`](../../packages/support/src/Twig/ComponentAttributeBag.php)
  — add a public static beside `toTokenList()`:

```php
/**
 * Resolve a component color value to its class fragment: the named-palette
 * classes ['sy-color', "sy-color-{$name}"], or [] for the gray default —
 * null / '' / an array / the literal 'gray' (the component's base CSS styles
 * gray; arbitrary/array palettes are the deferred ColorManager seam). Public
 * so the color() Twig function and PHP callers share one implementation,
 * mirroring toTokenList().
 *
 * @return list<string>
 */
public static function colorClasses(string|array|null $color): array
{
    if (! is_string($color) || $color === '' || $color === 'gray') {
        return [];
    }

    return ['sy-color', "sy-color-{$color}"];
}
```

- [`packages/support/src/Twig/Extension/AttributeExtension.php`](../../packages/support/src/Twig/Extension/AttributeExtension.php)
  — register a third function (**append after `classes`** so the existing
  `functions[0]==='attrs'` / `functions[1]==='classes'` assertions still hold —
  `color` becomes `functions[2]`) and add its backing method:

```php
new TwigFunction('color', $this->getColorClasses(...)),
```

```php
/**
 * Resolves a component color to its class fragment (a joined string, like
 * classes()), for nesting into any class list — attrs() sources, classes()
 * arguments, or a generateIconHtml() attributes map. Delegates to
 * ComponentAttributeBag::colorClasses() so template and PHP share one
 * implementation. Named-palette classes only; arbitrary/array colors are the
 * deferred ColorManager seam and yield '' (the gray default).
 */
public function getColorClasses(string|array|null $color): string
{
    return implode(' ', ComponentAttributeBag::colorClasses($color));
}
```

  Register `color` **without** `is_safe html` (like `classes()` — it returns a
  class string, not raw HTML).

- Refactor the four call sites (remove each `colorName`/`iconColorName` `{% set %}`
  and inline `color(...)`), each output-equivalent:
  - [`packages/schemas/templates/components/Icon.html.twig`](../../packages/schemas/templates/components/Icon.html.twig)
    — `class: ['sy-sc-icon', color(component.getColor)]`.
  - [`packages/schemas/templates/components/Text.html.twig`](../../packages/schemas/templates/components/Text.html.twig)
    — append `color(component.getColor)` to the class list (drop the `color`/`colorName`
    sets and the conditional-map entries).
  - [`packages/support/templates/components/IconButton/index.html.twig`](../../packages/support/templates/components/IconButton/index.html.twig)
    — add `color(color)` as a third `classes(...)` argument (drop the `colorName`
    set and its two conditional-map entries). `color(color)` = call the `color`
    function on the `color` prop — valid Twig, function and variable namespaces
    are distinct.
  - [`packages/support/templates/components/Section/index.html.twig`](../../packages/support/templates/components/Section/index.html.twig)
    — the section icon: `class: color(iconColor)` (drop the `iconColorName` set).

**Locked decisions.** `color()` returns a joined class string nested into the
existing class lists — not a Blade method chain, not a `{class: […]}` source map
(design §2). Component-agnostic (no component-type param). Named-palette classes
only; array/arbitrary → `''` (the `ColorManager` seam, deferred). Output-equivalent
(§6).

**Acceptance criteria.**
- **Unit** — extend
  [`ComponentAttributeBagTest.php`](../../packages/support/tests/Unit/Twig/ComponentAttributeBagTest.php):
  `colorClasses(null)` / `colorClasses('')` / `colorClasses('gray')` /
  `colorClasses(['shade' => '#fff'])` each `toBe([])`; `colorClasses('primary')`
  `toBe(['sy-color', 'sy-color-primary'])`; `colorClasses('danger')`
  `toBe(['sy-color', 'sy-color-danger'])`.
- **Unit** — extend
  [`AttributeExtensionTest.php`](../../packages/support/tests/Unit/Twig/Extension/AttributeExtensionTest.php):
  **update the existing "registers the attrs and classes functions" test** to
  `toHaveCount(3)` and assert `functions[2]->getName()` is `'color'`; add
  `getColorClasses('primary')` ⇒ `'sy-color sy-color-primary'`,
  `getColorClasses('gray')` / `getColorClasses(null)` / `getColorClasses([])` ⇒
  `''`.
- **Output equivalence (the gate)** — the existing render tests pass **unchanged**:
  [`IconComponentRenderTest`](../../packages/schemas/tests/Integration/Components/IconComponentRenderTest.php)
  (`sy-color sy-color-danger`; `not sy-color-` for default),
  [`TextRenderTest`](../../packages/schemas/tests/Integration/Components/TextRenderTest.php),
  [`IconButtonRenderTest`](../../packages/schemas/tests/Integration/Components/IconButtonRenderTest.php)
  (`sy-color sy-color-primary`/`-danger`),
  [`SectionRenderTest`](../../packages/schemas/tests/Integration/Components/SectionRenderTest.php).
  Run `composer test:schemas` — a diff in any asserted class string means the
  refactor changed output; fix the template, don't touch the test.
- **Parity docs** — record the two design divergences (`color()` shape;
  array/`ColorManager` seam) under
  [infrastructure.md#support-managers](infrastructure.md#support-managers).
- DoD gates green. **No asset build** — templates and PHP only; the `.sy-color-*`
  CSS already ships (`git status` must show no `assets/dist` change).

**Notes / pitfalls.** `color()` returns a **string** (not a list) so a stray
`{{ color(x) }}` is harmless and, when nested, re-splits/filters via
`ComponentAttributeBag::toTokenList` — including empty `''` → dropped, so the
gray/default output has no trailing space. `generateIconHtml` routes its
`attributes` through `ComponentAttributeBag` (one attribute implementation —
CLAUDE.md), so the nested class flattens/filters exactly as `attrs()` does; the
Icon/Section render tests confirm it. Keep the change PHP+Twig only — resist
touching CSS.

## Epic wrap-up (with S2, the last task to land)

- Flip the [roadmap.md](roadmap.md) Milestone 1 / step-1 row to done; link
  [support-enablers.md](support-enablers.md) + this file; correct the stale
  "minimal color layer (primary/gray CSS vars)" wording to note the CSS ships all
  seven palettes and this epic added the `color()` consumption helper (record the
  deferred `getVerticalAlignmentClass`-style class fragments + the `Operation`
  wiring as the remaining thread items).
- Confirm the design's [Divergences](support-enablers.md#divergences-record-when-code-lands)
  landed under `infrastructure.md#support-managers`.
