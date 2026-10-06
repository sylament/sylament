# ColorManager (support)

`Sylament\Support\Colors\ColorManager` — the palette registry. It holds what
`primary` and `danger` and any consumer-registered name resolve to, and it is the
one place that turns a colour plus a
[contrast policy](color-policies.md) into the class list or the inline
declarations a template renders. The palette data and the maths it measures with
are [`color.md`](color.md).

Templates reach it through two functions on
[`ColorExtension`](../../../packages/support/src/Twig/Extension/ColorExtension.php),
and they are tracked in different files because Filament reaches the same two
behaviours from different places. `sylamentStyles()` has a row below: its
Filament counterpart is the colour half of `AssetManager::renderStyles()`, and
nothing else here ports the asset manager. `colorAttributes()` does **not** have
a row: it is the port of `ComponentAttributeBag::color()`, whose row is in
[`component-attribute-bag.md`](component-attribute-bag.md#surface) alongside the
`grid()` / `gridColumn()` macros it was translated with, so its status lives
there and only there.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Colors/ColorManager.php`](../../../vendor/filament/support/src/Colors/ColorManager.php)
  — the registry, the class emission and the custom-style emission
- [`support/src/Assets/AssetManager.php`](../../../vendor/filament/support/src/Assets/AssetManager.php)
  — `renderStyles()`, whose colour half `sylamentStyles()` ports
- [`support/src/Facades/FilamentColor.php`](../../../vendor/filament/support/src/Facades/FilamentColor.php)
  — the facade the manager is reached through there

## Surface

Every point of this class. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`DEFAULT_COLORS`](../../../packages/support/src/Colors/ColorManager.php) | [`DEFAULT_COLORS`](../../../vendor/filament/support/src/Colors/ColorManager.php) | done | — | [diverges](#info-defaults-to-indigo-where-filament-uses-blue) |
| [`SHADES`](../../../packages/support/src/Colors/ColorManager.php) | — (Sylament addition) | done | — | [diverges](#a-registered-palette-must-fill-the-shade-grid-exactly) |
| [`register()`](../../../packages/support/src/Colors/ColorManager.php) | [`register()`](../../../vendor/filament/support/src/Colors/ColorManager.php) | done | — | [semantics](#registration-is-lazy-and-last-write-wins-per-name) |
| [`getColors()` / `getColor()`](../../../packages/support/src/Colors/ColorManager.php) | [`getColors()` / `getColor()`](../../../vendor/filament/support/src/Colors/ColorManager.php) | done | — | [diverges](#a-registered-palette-must-fill-the-shade-grid-exactly) · [diverges](#shade-aliases-are-not-supported) |
| [`getComponentClasses()`](../../../packages/support/src/Colors/ColorManager.php) | [`getComponentClasses()`](../../../vendor/filament/support/src/Colors/ColorManager.php) | done | — | [semantics](#an-unregistered-colour-name-emits-the-two-base-classes-and-nothing-else) · [diverges](#the-policy-cache-key-is-the-class-name-not-a-serialisation) |
| [`getComponentCustomStyles()`](../../../packages/support/src/Colors/ColorManager.php) | [`getComponentCustomStyles()`](../../../vendor/filament/support/src/Colors/ColorManager.php) | done | — | [diverges](#every-emitted-custom-property-carries-the---sy--prefix) |
| [`sanitizeCssColor()`](../../../packages/support/src/Colors/ColorManager.php) | [`Str::macro('sanitizeCssColor')`](../../../vendor/filament/support/src/SupportServiceProvider.php) | done | — | [diverges](#the-css-colour-sanitizer-is-a-method-on-the-manager-and-drops-rather-than-nulls) |
| [`sylament.colors` config node](../../../packages/support/src/SylamentSupportBundle.php) | [`FilamentColor::register()` in a service provider](../../../vendor/filament/support/src/Facades/FilamentColor.php) | done | — | [semantics](#the-config-node-is-a-variable-prototype-with-key-normalisation-off) |
| [`sylamentStyles()`](../../../packages/support/src/Twig/Extension/ColorExtension.php) | [`AssetManager::renderStyles()` — colour half](../../../vendor/filament/support/src/Assets/AssetManager.php) | done | — | [diverges](#sylamentstyles-emits-only-the-delta-over-the-shipped-css-floor) |
| `overrideShades()` / `getOverridingShades()` / `addShades()` / `getAddedShades()` / `removeShades()` / `getRemovedShades()` | [same names](../../../vendor/filament/support/src/Colors/ColorManager.php) | todo | — | [detail](#the-six-shade-mutation-methods-are-not-ported) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The six shade-mutation methods are not ported

`overrideShades()`, `addShades()` and `removeShades()` — with their three
getters — let a Filament **panel theme** restate which shades of a palette the
panel's compiled CSS should carry, and nothing reads them except the panel-side
asset pipeline. There is no panel here and no compiled-per-app stylesheet for
them to steer: Sylament ships one prebuilt `assets/dist`, and the shade set it
defines is `ColorManager::SHADES`, pinned against the stylesheet by
[`CssPaletteFloorTest`](../../../packages/support/tests/Architecture/CssPaletteFloorTest.php).

They are **todo** rather than **skipped** because the panels milestone is the
consumer that would make them mean something; until then, adding them would ship
three setters whose values nothing reads. Reversing that judgement means
deciding how a per-app shade set reaches the stylesheet at all, which is an
asset-pipeline question rather than a registry one.

## Semantics

Faithful ports that a reader re-deriving the behaviour from the source would
plausibly get wrong. Permanent, and true once the row is **done**.

### Registration is lazy and last-write-wins per name

`register()` only appends to a list — it neither validates nor resolves. The
whole list is walked once, on the first `getColors()` call, which unshifts
`DEFAULT_COLORS` in front of it and then merges name by name, so a later
registration of `primary` replaces the default and a later registration of
`brand` replaces an earlier one. A `Closure` registration is evaluated at that
same moment, through `EvaluatesClosures` — laziness is the point, since a closure
registered at bundle-load time gets to run after the container is up.

Three things follow that the signatures do not say. A palette written out shade
by shade is only validated when `getColors()` first runs — so a bad palette in
`sylament.colors` surfaces at first render, not at container compile. The result
is memoised in `cachedColors` with no invalidation, so a `register()` call made
after anything has rendered has no effect at all. And a registered closure
evaluates with an **empty-container** `Invoker`: the service definition is a
plain `set()` with explicit `args()`, not `->autowire()`, so the `#[Required]`
`setInvoker()` never runs and `getInvoker()` takes its `new Invoker(new
Container())` fallback. `fn () => [...]` works; `fn (SomeService $x) => [...]`
throws. Nothing needs the injection today, and closing it is one `->autowire()`
on the definition.

### An unregistered colour name emits the two base classes and nothing else

`getComponentClasses()` has three outcomes, and only the middle one is easy to
misread. A blank colour, or `gray` on a policy implementing
`HasDefaultGrayColor`, emits **nothing** — the component falls to its
`:not(.sy-color)` branch. A **known** colour emits `sy-color`, `sy-color-<name>`
and one shade class per slot the policy resolved. An **unknown** colour emits
`sy-color` and `sy-color-<name>` and stops: the marker is on the element, so the
`.sy-color` branch of the component's CSS applies, but no `.sy-color-<name>` rule
exists to point the dynamic palette anywhere and no slot variable is set.

The element therefore renders with its colour declarations invalid at
computed-value time — inherited or unset, never the default palette. That is
Filament's behaviour and is now ours; see
[styling.md](../../internals/styling.md#how-does-a-component-take-a-colour-per-instance) and
[decision 6](#an-unknown-colour-no-longer-falls-back-to-primary) below.

The map a policy returns is keyed from a closed set of eight — `bg` and `text`,
each also as `hover:`, `dark:` and `dark:hover:` — and that set exists only as
the `match` in `getComponentClasses()`. For a known colour name any other key
throws `LogicException: Invalid color mapping key [<key>].` at render time. The
inline-shade-map path, `getComponentCustomStyles()`, checks no key: it emits
whatever the policy returns as a `--sy-<key>` declaration, colons turned to
dashes, and a key no stylesheet reads paints nothing.

### The config node is a variable prototype with key normalisation off

`sylament.colors` mirrors `register()`'s
`array<string, array<int, string>|string>`, so a value is either a colour string
to generate a palette from or an explicit shade map. Because that is a union, the
prototype is a `variableNode()` — `icons` next to it uses a `scalarPrototype()`
and could not express the map arm.

`->normalizeKeys(false)` is on it for the reason `docs/internals/bundles.md` gives for the icons
node: `ArrayNode::preNormalize()` rewrites any key containing a dash and no
underscore, so a palette registered as `brand-alt` would silently become
`brand_alt` and match no class the templates emit.

The `Color::` constants reach the node from YAML too: Symfony's
`YamlFileLoader` parses config files with `Yaml::PARSE_CONSTANT`, so
`rose: !php/const Sylament\Support\Colors\Color::Rose` under `sylament.colors`
registers the catalog palette. PHP-side registration stays available by
injecting the service — `$colorManager->register(['primary' => Color::Rose])`.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### `info` defaults to Indigo where Filament uses Blue

Filament maps `info` to Blue. Sylament maps it to Indigo. It is the only entry of
`DEFAULT_COLORS` that differs, and it was chosen on measurement rather than
taste: running the ported
[`ButtonComponent`](../../../packages/support/src/Colors/Components/ButtonComponent.php)
policy over each candidate's four filled-button states — resting, hover, dark,
dark hover — Blue fails hover in both modes at 3.74:1, and Indigo clears all
four. Sky, which was Sylament's own `info` before this epic, fails the dark state
at 3.97:1; Amber, Filament's `primary`, clears all four, which is what vindicated
keeping it. Across those four candidates for the two slots, Amber and Indigo are
the only ones clean in every state, and adopting them turns three failing states
into zero.

The claim is about *those* candidates, not about the catalog: 15 of the 26
palettes `Color::all()` ships clear all four states, and the eleven that do not
are red, emerald, teal, cyan, sky, blue, violet, purple, fuchsia, pink and rose.

One consequence to state plainly, because it looks like a bug: Filament maps
**both** `primary` and `warning` to Amber, so the two render identically. That is
Filament's shipped behaviour and it is inherited deliberately rather than
overlooked. Changing either would be a design decision about Sylament's default
look, not a port fix.

`danger` (Red) is the palette that does not come out clean, and it is Filament's
too: a filled danger button fails AA in its hover and dark-hover states at
3.85:1. That is not fixable by shade selection — red-500, the shade the
candidate list hovers to, admits **no** readable text at all, neither white nor
any shade of its own palette, the best being red-950 at 4.34:1. Both facts are
pinned by exact-list assertions in
[`PolicyContrastTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyContrastTest.php),
so the pair goes red in either direction — closing it means changing which shades
a filled button paints, a visible change to every danger button rather than a
port fix.

The CSS floor moves with `DEFAULT_COLORS` and is pinned to it shade by shade by
[`CssPaletteFloorTest`](../../../packages/support/tests/Architecture/CssPaletteFloorTest.php).

### A registered palette must fill the shade grid exactly

`ColorManager::SHADES` is a Sylament-only constant naming the eleven steps
(50 … 950) that `Color::generatePalette()` produces and that
`utilities.css` hand-writes a rule for. Any palette written out shade by shade —
registered by name, or handed inline to `color()` — is checked against it by
`assertCompletePalette()`, and an `InvalidArgumentException` names the palette
and lists what is wrong. Both directions are rejected: a **missing** grid shade,
and a shade **off** the grid.

Filament has no such check, and the two failure modes it leaves open are why this
one exists. A palette missing shades does not fail where anyone would look for
it — `findShade()` walks whatever it is handed and settles on some other shade or
on the declared fallback, so the component renders in the wrong colour rather
than erroring, and a policy that indexes a shade directly (`$color[50]` on the
badge, `$color[950]` on the dropdown item) dies inside the policy instead. A
shade off the grid renders nothing at all: the `sy-bg-color-<shade>` /
`sy-text-color-<shade>` utilities and the `@theme inline` entries that read them
are one per step, so an emitted class no rule matches sets no variable, and the
declaration reading it is dropped at computed-value time with nothing in the
console.

Filament degrades an empty palette silently and crashes on a sparse one, so
there is no parity argument in either direction; consistency was chosen.

A palette generated from a colour string always carries the whole grid, so only
an explicit map can trip either check.

### Shade aliases are not supported

Filament allows one shade of a palette to *point at* another by writing an int
value (`950 => 900`), or at white by writing `0`;
`AssetManager::resolveColorShadeFromPalette()` follows the chain when it emits
the CSS. Sylament types a palette `array<int, string>` and converts every value,
so an int value is rejected — measured, it surfaces as
`InvalidArgumentException: Unable to parse the color "900"`, the
[unparseable-colour throw](color.md#an-unparseable-colour-throws-instead-of-yielding-black).

The feature has no consumer here: nothing in the port writes an aliased palette,
and the one place `0` still means white is the *colour map* — a policy may resolve
a text slot to shade 0, which `getComponentCustomStyles()` emits as
`oklch(1 0 0)` and `utilities.css` carries `sy-text-color-0` rules for. That is a
map value, not a palette value, and it is unaffected. Reversing this means
widening the palette type and following the chain in both emitters.

### Every emitted custom property carries the `--sy-` prefix

Filament's inline styles are `--color-600: …` and `--text: var(--color-900)`;
Sylament's are `--sy-color-600: …` and `--sy-text: var(--sy-color-900)`. Same for
the `:root` and swap-class output of `sylamentStyles()`, and for the slot
variables the component stylesheets read.

The reasoning is in [`styling.md`](../../internals/styling.md#why-are-per-instance-values-inline-variables) and is not colour-specific:
custom properties inherit, `--primary-600` and `--text` are among the most
common token names in the wild, and Sylament embeds into a page it does not own —
one `:root { --text: … }` in the host app would repaint every Sylament
component. The porting cost is close to nil, because component CSS reaches palettes through
`@apply bg-color-600` and the `@theme inline` bridge makes the two spellings
identical. Measured across the per-component stylesheets — the files a port
copies — Sylament's carry **0** raw palette `var()` references, and Filament's
carry **6**, all of them inside `color-mix()` expressions in exactly two files
(`support/components/callout.css` and `notifications/notification.css`), neither
of which is ported yet. When they are, six declarations gain a `sy-` on each
side; the aggregator and utility files, where the references run into the
hundreds, are ours already.

### The policy cache key is the class name, not a serialisation

Filament keys its two per-component caches on `serialize($component)` when it is
handed an instance. Sylament keys on `$component::class`.

It has to. A Sylament policy is a container service holding a `ColorManager`,
which holds the container; `serialize()` would walk that whole graph, and
`ContainerBuilder` is not serialisable. The key is not lossy either, because a
policy carries no per-instance state — the one variant Filament expresses as
constructor state, the outlined button, is
[a subclass here](color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag),
so the class *is* the whole identity. Pinned by "caches a policy instance apart
from a sibling policy sharing its dependencies" in
[`ColorManagerTest`](../../../packages/support/tests/Unit/Colors/ColorManagerTest.php).

### The CSS colour sanitizer is a method on the manager and drops rather than nulls

Filament registers `sanitizeCssColor` as a `Str` macro (plus a `Stringable`
mirror) in its service provider, parses several colour syntaxes, and returns
`null` for anything it rejects — which `getComponentCustomStyles()` then coerces
to `''`. Sylament has no macro system
([Macroable is not ported](component.md#macroable-is-not-ported)), so it needed a
home; it is a public method on the manager rather than a function in
[`helpers.php`](helpers.md), which it is pure enough for, because both of its
callers are the manager's own emitters and that file is for ports of Filament's
own helper functions.

It is also stricter and simpler: one allow-list regex over the whole value
(`[a-zA-Z0-9\s.,%()#/-]`), and any value carrying a character outside it — a
quote, a semicolon, a brace, an angle bracket — is dropped whole, returning `''`.
That is the security boundary for both sinks, since the value is interpolated raw
into a `style` attribute here and into a `<style>` element in `sylamentStyles()`.
Palette *names* are stripped separately with `[^a-zA-Z0-9_-]`; shade keys are not
stripped at all, because
[the grid check](#a-registered-palette-must-fill-the-shade-grid-exactly)
has already rejected anything that is not one of eleven integers.

### `sylamentStyles()` emits only the delta over the shipped CSS floor

Filament's `AssetManager::renderStyles()` emits a `:root` block for **every**
palette in the registry and a swap class for every non-default name, because its
compiled `theme.css` defines no palette values at all — verified in
`filament/filament/dist/theme.css`. A Filament page that omits `@filamentStyles`
has no colours.

Sylament keeps the six default palettes in the shipped stylesheet
([styling.md on the CSS floor](../../internals/styling.md#why-are-the-default-palettes-defined-twice)), so
`sylamentStyles()` emits only what a consumer added on top: a `:root` override
for a palette redefined or newly named, a `.sy-color-<name>` swap class for a
name the stylesheet has none for, and an **empty string** — not a bare `<style>`
element — when nothing is registered.

Two consequences follow from the floor and the registry being separate. Overriding
a `--sy-<palette>-<shade>` token in an application's own CSS changes the value
painted but not the shade a policy picks, because the policies measure the
palette `ColorManager` holds — so anything beyond a nudge is registered instead.
And on a page that never calls `sylamentStyles()`, a newly registered name paints
nothing, since no `.sy-color-<name>` rule points the dynamic palette at it, while
a redefined default keeps the floor's shipped values in CSS with its shades picked
against the new palette.

The trade is deliberate. Filament gets away with requiring the directive because
the panel owns the page layout and its scaffolding generates one containing it;
Sylament has neither in M1 and its pitch is embedding into an app it does not
control, where a missing call would render every component colourless with no
error. The cost is that the six palettes exist twice — once in
`ColorManager::DEFAULT_COLORS` and once in `index.css` — which is exactly what
[`CssPaletteFloorTest`](../../../packages/support/tests/Architecture/CssPaletteFloorTest.php)
was written to make safe.

### An unknown colour no longer falls back to primary

Before this epic, `index.css` carried a `:root { --sy-color-50 … --sy-color-950 }`
block defaulting the dynamic palette to primary, so a colour name with no
`.sy-color-<name>` rule rendered as primary. Those eleven declarations are
deleted. An unknown name now renders with its colour declarations unresolved,
matching Filament.

This retires a divergence rather than creating one: the old behaviour made a
typo'd or unregistered palette name look deliberate, which is the worst possible
reading of it. The registry is what makes the change affordable — a name that
ought to work can now be registered, where before there was nothing to register
it with.
