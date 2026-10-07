# HasName (schemas)

`Sylament\Schemas\Components\Concerns\HasName` — a two-method trait holding a component's machine name: a `name` property, the `name()` setter, and the `getName()` accessor.
It ports Filament's identically-named base trait, which lives in the `schemas` namespace but is consumed by the field layers above it.
Its one consumer today is [`Sylament\Forms\Components\Field`](../../../packages/forms/src/Components/Field.php); it stays a shared row here (rather than folding into `forms`) because Filament shares `HasName` across ~6 field/entry classes and Sylament will too as `infolists` and the remaining `forms` components land.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Concerns/HasName.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) — the property, setter, and accessor ported here

## Surface

Every point of this trait.
_Detail_ carries a **diverges** link to the permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`name()`](../../../packages/schemas/src/Components/Concerns/HasName.php) | [`name()`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | [diverges](#hasname-is-nullable-unlike-filaments-required-string-name) |
| [`getName()`](../../../packages/schemas/src/Components/Concerns/HasName.php) | [`getName()`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | [diverges](#hasname-is-nullable-unlike-filaments-required-string-name) |

Both rows are **done** — the setter and accessor are ported faithfully and covered directly by [`HasNameTest.php`](../../../packages/schemas/tests/Unit/Components/Concerns/HasNameTest.php) (default `null`, `name('title')` set, reset with `null`) via [`HasNameFixture`](../../../packages/schemas/tests/Fixtures/Components/Concerns/HasNameFixture.php), so neither has a write-up here.

## Divergences

### HasName is nullable, unlike Filament's required string name

Filament types the property `string $name` with no default and returns `getName(): string` — a field always has a name, and `Field::make()` throws when it cannot derive one.
Sylament types it `?string $name = null`, with `name(?string)` and `getName(): ?string`, because [`Field::make(?string $name = null)`](../../../packages/forms/src/Components/Field.php) accepts a null name and the label fallbacks tolerate it rather than throwing.
Reverse it by adopting Filament's throw-on-missing-name contract in the `Field` port; until then the nullability is what the consumer requires.
