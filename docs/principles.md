# Principles

Sylament ports Filament's API to Symfony. These are the stances that decide what
that means when the two pull in different directions, so they outrank the porting
rules in [CLAUDE.md](../CLAUDE.md) rather than sitting beside them.

Each one states the position, the test that makes it decidable, and what it does
*not* cover. They are judgment rules, not gates — nothing in CI checks them.
Where one is knowingly broken, the
[divergence convention](progress/README.md#divergences-are-addressable) is where
the reason gets written down.

## 1. Symfony idiom, not Laravel idiom

Sylament is a Symfony toolkit, built for Symfony applications and for developers
who write Symfony. The framework's conventions are the house style, and a Laravel
pattern does not become acceptable here by arriving through Filament.

**What this binds is how a thing is built. What it exempts is what the user
types.** `Action::make('save')->label(fn ($component) => …)` stays exactly as it
is — Filament's public API is the product, and a migrating user greps for it.
The principle governs everything behind that surface.

### The test

Laravel origin is what makes a pattern worth examining. It is never the verdict:
plenty of good ideas are Laravel's, and a pattern we invent ourselves fails this
test just as easily. What is rejected is the property, not the pedigree.

1. **A dependency that arrives from anywhere but `$this`.** Facades, ambient
   globals, service location, static reach into a service. Symfony's answer is
   constructor injection on a container-built object, or `#[Required]` setter
   injection. A static method that needs a dependency is a signal to drop
   `static`, not to add a locator.
2. **An API that neither PHPStan nor an IDE can see.** `__call()`-based method
   injection, magic properties, string-addressed dispatch — this is why
   [Macroable is not ported](progress/support/component.md#macroable-is-not-ported).

Reimplementing something Symfony already ships is the same mistake wearing a
different hat, but it has nothing to do with Laravel and is governed by
[§2](#2-prefer-the-ecosystem-over-invention) instead.

### What passes

A pure value transformation with no framework coupling, whoever wrote it first.
`blank()` and `filled()` are Laravel's, and they are namespaced free functions
over `mixed` — no container, no global state, nothing a Symfony developer has to
learn. Copying a *function* is not importing an *idiom*.

**Naming is out of scope.** This principle governs mechanisms, not vocabulary.
A method name Laravel also uses — `*OrFail`, `make()`, `configure()` — costs a
Symfony developer nothing to read and takes nothing away from the framework. The
question is never whether a word appears in Laravel; it is whether the thing
behind it makes Symfony behave less like Symfony. `getSchemaComponentOrFail()`
was briefly renamed to dodge an Eloquent association, which was this principle
misapplied by the person who had just finished writing it — evidence enough that
the limit was worth stating.

Deferral is also not rejection. `Conditionable` and `Tappable`
[are skipped](progress/support/component.md#conditionable-and-tappable-are-not-ported)
because nothing wants them yet, and that note says so in as many words — a later
need reverses it without reopening this principle.

### Mechanisms, not names

[`renames.md`](progress/renames.md#idiom) maps the *naming* translations:
`Htmlable` → `Markup`, Blade → Twig, Alpine → Stimulus. These are the *mechanism*
ones, which have no rename to record because the Laravel side is simply absent.

| Laravel | Sylament |
| --- | --- |
| `app()`, facades, `resolve()` | constructor or `#[Required]` injection |
| `data_get()` / `Arr::get()` against an object | `symfony/property-access` |
| `__()` | Symfony translation |
| `config()` | bundle configuration and injected parameters |

The table is not exhaustive, and an unmapped case is a decision waiting to be
made rather than a licence to reach for the Laravel shape. Validation was the
open one, and M1 · 1 decided it: Filament emits Laravel rule strings, which have
no Symfony analogue, so
[`rule()` and `rules()` became `constraint()` and `constraints()` over Symfony `Constraint` objects](progress/forms/can-be-validated.md#rule-and-rules-become-constraint-and-constraints-over-symfony-constraint-objects).

## 2. Prefer the ecosystem over invention

Symfony's ecosystem is a reason to choose Symfony, so Sylament uses it rather
than routing around it: `symfony/ux-icons` renders icons,
`symfony/ux-twig-component` renders components, `symfony/stimulus-bundle` wires
behaviour. When something we need already exists there, adopting it is the
default and building our own is the case that has to be argued.

### Four rungs, not two

"Use it or build it" is a false binary. In order of preference:

| Rung | What it means | Example |
| --- | --- | --- |
| **Use** | The package fits. Depend on it directly. | ux-twig-component, StimulusBundle |
| **Wrap** | The package works, our API differs. Their maintenance, our ergonomics. | `IconManager` over ux-icons' `IconRendererInterface` |
| **Fill a gap beside it** | Keep the package, add only the piece it lacks. | `PropertyAccessor`'s array trio beside `symfony/property-access` |
| **Replace** | Build our own instead. | — nothing today |

### "We can do it better" means wrap

Wrapping answers almost every version of that thought, and
[`IconManager`](../packages/support/src/Icons/IconManager.php) is the shape: it
holds ux-icons' `IconRendererInterface`, exposes the API our components want, and
throws naming `UXIconsBundle` when the package is absent. We get the API we
wanted; they keep maintaining the renderer.

Replacing outright needs a **named, checkable gap** — a sentence a reader could
prove wrong.
[`PropertyAccessor`](progress/schemas/property-accessor.md#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal)
sets the standard: *PropertyAccess is host-only and has no key removal.*

Not sufficient, in any combination: cleaner, nicer API, we only need a tenth of
it, their naming is off, it pulls in too much.

### Adopting still costs a dependency

Preferring the ecosystem does not suspend the
[dependency-declaration rules](../.claude/rules/manifests.md),
which are deliberately strict about which package declares what. Nor does it make
adoption automatic: a new dependency is proposed and agreed, never added in
passing. A package we adopt is a package every consumer installs — the ecosystem
is the default, not a free pass.
