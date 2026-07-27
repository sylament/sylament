# ActionGroup Base + Dropdown Primitive Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A reusable Floating-UI dropdown primitive in `support` and an `ActionGroup` base in `actions` whose model surface (`getFlatActions()`, visibility composition, propagation) is final for the upcoming schemas↔actions integration epic.

**Architecture:** The dropdown is a generic support-level anonymous Twig component (`<twig:SylamentSupport:Dropdown>`) driven by a Stimulus controller that bundles `@floating-ui/dom` into its dist entry. `ActionGroup extends ViewComponent` composes it: an eager name-keyed flat-actions map, trigger-template machinery (icon-button default, button variant), and a grouped-item template pushed onto child actions via a new `ViewComponent::defaultTemplate()` precedence hook. Mount resolution is untouched — grouped items emit the same `mountAction` live action as standalone buttons.

**Tech Stack:** PHP 8.5 / Symfony bundles, Pest, PHPStan level max, Twig + ux-twig-component + StimulusBundle helpers, TypeScript strict + Stimulus 3, esbuild (per-file, plus one `--bundle` entry), Tailwind `@apply`, `@floating-ui/dom` ^1.8.0.

Spec: `docs/superpowers/specs/2026-07-27-action-group-base-design.md`.

## Global Constraints

- `declare(strict_types=1)` in every PHP file; PHPStan **level max** stays green; run `composer format` before each commit (never hand-format).
- Filament API names preserved; view→template is the recorded systematic translation (no rename-table entries).
- No new PHP dependencies. Exactly one new JS dependency: `@floating-ui/dom` `^1.8.0` in `packages/support/assets/package.json` — bundled into dist, never a consumer install step.
- CSS: semantic `sy-*` classes only, `@apply` authoring in `packages/support/assets/src/styles/components/`, dynamic values via `--sy-*` inline variables. No Tailwind utilities in Twig.
- TS: strict, no `any`, no non-null `!` (Biome errors), `import type` for type-only imports. Relative imports need `.js` extensions (none required here; `@floating-ui/dom` and `@hotwired/stimulus` are bare specifiers).
- Tests: Pest; `Unit/` (no kernel) and `Integration/` (kernel) buckets mirroring `src/`; fixtures in `tests/Fixtures/` mirroring `src/`; inside `it()` use `$this::getContainer()`, never `static::`.
- Progress-table updates land **in the same commit** as the code they describe.
- Commit messages: conventional (`feat(support): …`, `feat(actions): …`, `docs(progress): …`), each ending with the `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>` trailer.
- Deferred surfaces (recorded, never built here): badge/button-group/grouped/link trigger views, submenu rendering, dropdown teleport + auto-placement, `toArray()`/`Arrayable`, table/bulk concerns, translator-backed labels, schemas-side integration.

---

### Task 1: `ViewComponent::defaultTemplate()`

**Files:**
- Modify: `packages/support/src/Components/ViewComponent.php` (add `$defaultTemplate` + setter; change `getTemplate()`)
- Test: `packages/support/tests/Unit/Components/ViewComponentTest.php` (create if absent — mirror-tree rule; if it exists, append the `it()` blocks)
- Modify: `docs/progress/support/view-component.md` (add the `defaultTemplate()` surface row, status **done**)

**Interfaces:**
- Consumes: existing `ViewComponent::template()` / `getTemplate()` (`packages/support/src/Components/ViewComponent.php:22-37`).
- Produces: `defaultTemplate(string|Closure|null $template): static` and `getTemplate(): ?string` resolving explicit template first, then default. Task 6 calls `->defaultTemplate(Action::GROUPED_TEMPLATE)`.

- [ ] **Step 1: Write the failing tests**

In `packages/support/tests/Unit/Components/ViewComponentTest.php` (header if creating: `declare(strict_types=1);` + `namespace Sylament\Support\Tests\Unit\Components;` + `use Sylament\Support\Components\ViewComponent;`):

```php
it('resolves the default template when no explicit template is set', function (): void {
    $component = new class extends ViewComponent {};

    expect($component->getTemplate())->toBeNull()
        ->and($component->defaultTemplate('default.html.twig')->getTemplate())->toBe('default.html.twig');
});

it('lets an explicit template win over the default', function (): void {
    $component = new class extends ViewComponent {};

    $component->defaultTemplate('default.html.twig')->template('explicit.html.twig');

    expect($component->getTemplate())->toBe('explicit.html.twig');
});

it('evaluates a default template closure', function (): void {
    $component = new class extends ViewComponent {};

    expect($component->defaultTemplate(fn (): string => 'closure.html.twig')->getTemplate())
        ->toBe('closure.html.twig');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest -c packages/support/phpunit.dist.xml --test-directory=packages/support/tests --filter=ViewComponentTest`
Expected: FAIL — `Call to undefined method ...::defaultTemplate()`.

- [ ] **Step 3: Implement**

In `packages/support/src/Components/ViewComponent.php`, add below the `$template` property and change `getTemplate()`:

```php
    protected string|Closure|null $defaultTemplate = null;
```

```php
    public function defaultTemplate(string|Closure|null $template): static
    {
        $this->defaultTemplate = $template;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->evaluate($this->template) ?? $this->evaluate($this->defaultTemplate);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest -c packages/support/phpunit.dist.xml --test-directory=packages/support/tests --filter=ViewComponentTest`
Expected: PASS. Then `composer test:support` — everything else stays green.

- [ ] **Step 5: Update the ledger**

In `docs/progress/support/view-component.md`, add a surface row for `defaultTemplate()` (Ports: Filament `ViewComponent::defaultView()`, `vendor/filament/support/src/Components/ViewComponent.php:65`; Status: done) following that file's existing table format.

- [ ] **Step 6: Format + commit**

```bash
composer format
git add packages/support/src/Components/ViewComponent.php packages/support/tests/Unit/Components/ViewComponentTest.php docs/progress/support/view-component.md
git commit -m "feat(support): ViewComponent::defaultTemplate() precedence hook

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Dropdown Twig component + CSS

**Files:**
- Create: `packages/support/templates/components/Dropdown/index.html.twig`
- Create: `packages/support/assets/src/styles/components/dropdown.css`
- Modify: `packages/support/assets/src/styles/components.css` (add the import)
- Test: `packages/actions/tests/Integration/Components/DropdownRenderTest.php`
- Create: `docs/progress/support/dropdown.md`
- Modify: `docs/progress/support/README.md` (add the Dropdown row), `CLAUDE.md` (add Dropdown to support's anonymous-components list in the "Twig components and template layout" section)

**Interfaces:**
- Consumes: `attrs()`/`classes()` Twig helpers (support `AttributeExtension`), `stimulus_controller`/`stimulus_action`/`stimulus_target` (StimulusBundle), `markup()` prop pattern from `packages/support/templates/components/Modal/index.html.twig`.
- Produces: `<twig:SylamentSupport:Dropdown>` with props `placement` (default `'bottom-start'`), `offset` (default `8` — Filament's blade default), `flip` (default `true`), `width` (default `null`), `maxHeight` (default `null`); blocks `trigger` and default content. Markup contract used by Task 9: `.sy-dropdown` root carrying the `sylament--support--dropdown` controller, `.sy-dropdown-trigger` wrapper (trigger target, click→toggle), `.sy-dropdown-panel` (panel target, `hidden` until opened).

- [ ] **Step 1: Write the failing render test**

`packages/actions/tests/Integration/Components/DropdownRenderTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

/**
 * Covers the support-level `<twig:SylamentSupport:Dropdown>` primitive. It lives in
 * the actions suite because only a kernel registering TwigComponentBundle exercises
 * the support Twig components — same placement as the Button/Modal render tests.
 */
function renderDropdown(string $template): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render();
}

it('renders a positioned dropdown shell with trigger and hidden panel', function (): void {
    $html = renderDropdown(<<<'TWIG'
        <twig:SylamentSupport:Dropdown>
            <twig:block name="trigger"><button type="button">Open</button></twig:block>
            <span>Panel content</span>
        </twig:SylamentSupport:Dropdown>
        TWIG);

    expect($html)->toContain('sy sy-dropdown')
        ->toContain('data-controller="sylament--support--dropdown"')
        ->toContain('data-sylament--support--dropdown-placement-value="bottom-start"')
        ->toContain('data-sylament--support--dropdown-offset-value="8"')
        ->toContain('data-sylament--support--dropdown-flip-value="true"')
        ->toContain('sy-dropdown-trigger')
        ->toContain('data-action="click->sylament--support--dropdown#toggle"')
        ->toContain('data-sylament--support--dropdown-target="trigger"')
        ->toContain('<button type="button">Open</button>')
        ->toContain('sy-dropdown-panel')
        ->toContain('data-sylament--support--dropdown-target="panel"')
        ->toContain(' hidden')
        ->toContain('Panel content');
});

it('applies placement, width, and max-height configuration', function (): void {
    $html = renderDropdown(<<<'TWIG'
        <twig:SylamentSupport:Dropdown placement="top-end" width="16rem" maxHeight="20rem" :flip="false">
            <twig:block name="trigger"><button type="button">Open</button></twig:block>
            <span>Panel content</span>
        </twig:SylamentSupport:Dropdown>
        TWIG);

    expect($html)->toContain('data-sylament--support--dropdown-placement-value="top-end"')
        ->toContain('data-sylament--support--dropdown-flip-value="false"')
        ->toContain('--sy-dropdown-width: 16rem;')
        ->toContain('--sy-dropdown-max-height: 20rem;');
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=DropdownRenderTest`
Expected: FAIL — `Unknown component "SylamentSupport:Dropdown"`.

- [ ] **Step 3: Write the component template**

`packages/support/templates/components/Dropdown/index.html.twig`:

```twig
{% props
    content = markup(block('content') ?? null),
    flip = true,
    maxHeight = null,
    offset = 8,
    placement = 'bottom-start',
    trigger = markup(block('trigger') ?? null),
    width = null %}

{% set dropdownController = 'sylament--support--dropdown' %}

<div
    {{ attributes.defaults({
        class: classes('sy sy-dropdown'),
    }) }}
    {{ stimulus_controller(dropdownController, {
        placement: placement,
        offset: offset,
        flip: flip,
    }) }}
>
    <div
        class="sy-dropdown-trigger"
        {{ stimulus_target(dropdownController, 'trigger') }}
        {{ stimulus_action(dropdownController, 'toggle', 'click') }}
    >
        {{ trigger }}
    </div>

    <div
        class="sy-dropdown-panel"
        hidden
        {{ stimulus_target(dropdownController, 'panel') }}
        {% if width or maxHeight %}style="{{ width ? '--sy-dropdown-width: ' ~ width ~ ';' }}{{ maxHeight ? '--sy-dropdown-max-height: ' ~ maxHeight ~ ';' }}"{% endif %}
    >
        {{ content }}
    </div>
</div>
```

- [ ] **Step 4: Write the CSS**

`packages/support/assets/src/styles/components/dropdown.css`:

```css
.sy-dropdown {
    @apply relative inline-block;
}

.sy-dropdown-trigger {
    @apply inline-flex;
}

.sy-dropdown-panel {
    @apply absolute z-10 overflow-y-auto rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10;
    width: var(--sy-dropdown-width, max-content);
    max-height: var(--sy-dropdown-max-height, none);
}

.sy-dropdown-item {
    @apply flex w-full items-center gap-2 rounded-md px-3 py-2 text-start text-sm text-gray-700 outline-none hover:bg-gray-950/5 focus-visible:bg-gray-950/5 dark:text-gray-200 dark:hover:bg-white/5 dark:focus-visible:bg-white/5;

    &.sy-disabled {
        @apply pointer-events-none opacity-70;
    }
}
```

Append to `packages/support/assets/src/styles/components.css`:

```css
@import './components/dropdown.css' layer(components);
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=DropdownRenderTest`
Expected: PASS. Then `pnpm build` from repo root — CSS compiles cleanly.

- [ ] **Step 6: Ledger + CLAUDE.md**

Create `docs/progress/support/dropdown.md` following `docs/progress/support/modal.md`'s structure (reference: Filament `support/resources/views/components/dropdown/index.blade.php`): rows for the component shell/props (**done**), the Stimulus positioning controller (**in progress**, done by Task 3), and deferred rows for teleport, `auto-placement`, size middleware, and submenu support. Add the row link in `docs/progress/support/README.md`. In `CLAUDE.md`, extend the anonymous-components example list (`support's Modal/Button/Icon/IconButton/Section`) with Dropdown.

- [ ] **Step 7: Format + commit**

```bash
composer format
git add packages/support/templates/components/Dropdown packages/support/assets/src/styles/components/dropdown.css packages/support/assets/src/styles/components.css packages/actions/tests/Integration/Components/DropdownRenderTest.php docs/progress/support/dropdown.md docs/progress/support/README.md CLAUDE.md
git commit -m "feat(support): Dropdown anonymous component shell + styles

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Dropdown Stimulus controller + bundled `@floating-ui/dom`

**Files:**
- Create: `packages/support/assets/src/controllers/dropdown_controller.ts`
- Modify: `packages/support/assets/package.json` (add dependency; split `build:js` / `watch:js`)
- Modify: `pnpm-lock.yaml` (via `pnpm install`), committed `packages/support/assets/dist/` (via `pnpm build`)
- Modify: `CLAUDE.md` ("Commands" JS section + TS conventions line about deps)

**Interfaces:**
- Consumes: the Task 2 markup contract (targets `trigger`/`panel`, values `placement`/`offset`/`flip`, `toggle` action).
- Produces: the working `sylament--support--dropdown` controller. No PHP-visible surface.

- [ ] **Step 1: Add the dependency**

In `packages/support/assets/package.json`, add:

```json
"dependencies": {
    "@floating-ui/dom": "^1.8.0"
}
```

Run from repo root: `pnpm install`
Expected: lockfile gains `@floating-ui/dom` pinned at 1.8.x.

- [ ] **Step 2: Write the controller**

`packages/support/assets/src/controllers/dropdown_controller.ts`:

```ts
import type { Placement } from "@floating-ui/dom";
import {
  autoUpdate,
  computePosition,
  flip as flipMiddleware,
  offset as offsetMiddleware,
  shift as shiftMiddleware,
} from "@floating-ui/dom";
import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLElement> {
  static targets = ["trigger", "panel"];

  static values = {
    placement: { type: String, default: "bottom-start" },
    offset: { type: Number, default: 8 },
    flip: { type: Boolean, default: true },
  };

  declare readonly triggerTarget: HTMLElement;
  declare readonly panelTarget: HTMLElement;
  declare readonly placementValue: string;
  declare readonly offsetValue: number;
  declare readonly flipValue: boolean;

  #stopPositioning: (() => void) | null = null;

  #onPointerDown = (event: PointerEvent): void => {
    if (event.target instanceof Node && !this.element.contains(event.target)) {
      this.close();
    }
  };

  #onKeyDown = (event: KeyboardEvent): void => {
    if (event.key === "Escape") {
      this.close();
      this.#focusableTrigger()?.focus();
    }
  };

  connect(): void {
    this.#focusableTrigger()?.setAttribute("aria-haspopup", "true");
    this.#syncExpanded(false);
  }

  disconnect(): void {
    this.close();
  }

  toggle(): void {
    if (this.panelTarget.hidden) {
      this.open();

      return;
    }

    this.close();
  }

  open(): void {
    if (!this.panelTarget.hidden) {
      return;
    }

    this.panelTarget.hidden = false;
    this.#syncExpanded(true);

    this.#stopPositioning = autoUpdate(this.triggerTarget, this.panelTarget, () => {
      void this.#position();
    });

    document.addEventListener("pointerdown", this.#onPointerDown);
    document.addEventListener("keydown", this.#onKeyDown);
  }

  close(): void {
    if (this.panelTarget.hidden) {
      return;
    }

    this.#stopPositioning?.();
    this.#stopPositioning = null;

    document.removeEventListener("pointerdown", this.#onPointerDown);
    document.removeEventListener("keydown", this.#onKeyDown);

    this.panelTarget.hidden = true;
    this.#syncExpanded(false);
  }

  async #position(): Promise<void> {
    const { x, y } = await computePosition(this.triggerTarget, this.panelTarget, {
      placement: this.placementValue as Placement,
      middleware: [
        offsetMiddleware(this.offsetValue),
        ...(this.flipValue ? [flipMiddleware()] : []),
        shiftMiddleware({ padding: 8 }),
      ],
    });

    this.panelTarget.style.left = `${x}px`;
    this.panelTarget.style.top = `${y}px`;
  }

  #syncExpanded(isOpen: boolean): void {
    this.#focusableTrigger()?.setAttribute("aria-expanded", isOpen ? "true" : "false");
  }

  #focusableTrigger(): HTMLElement | null {
    return this.triggerTarget.querySelector<HTMLElement>("button, a, [tabindex]") ?? this.triggerTarget;
  }
}
```

- [ ] **Step 3: Split the build so this one entry bundles**

In `packages/support/assets/package.json` `scripts`, replace `build:js` and `watch:js`:

```json
"build:js": "esbuild $(find src -name '*.ts' ! -name 'dropdown_controller.ts') --outdir=dist --outbase=src --format=esm --sourcemap=linked && esbuild src/controllers/dropdown_controller.ts --bundle --external:@hotwired/stimulus --outdir=dist --outbase=src --format=esm --sourcemap=linked",
"watch:js": "concurrently -n js,dropdown -c blue,magenta \"esbuild $(find src -name '*.ts' ! -name 'dropdown_controller.ts') --outdir=dist --outbase=src --format=esm --sourcemap=inline --watch=forever\" \"esbuild src/controllers/dropdown_controller.ts --bundle --external:@hotwired/stimulus --outdir=dist --outbase=src --format=esm --sourcemap=inline --watch=forever\""
```

(`concurrently` is already a devDependency of this workspace — the top-level `watch` script uses it.)

- [ ] **Step 4: Verify types, lint, build, bundling**

Run from repo root:
- `pnpm test:types` → PASS
- `pnpm test:lint` → PASS
- `pnpm build` → PASS

Then verify the bundle actually inlined Floating UI and stayed ESM with Stimulus external:

```bash
grep -c "computePosition" packages/support/assets/dist/controllers/dropdown_controller.js   # >= 1 (implementation inlined)
grep -c "@floating-ui" packages/support/assets/dist/controllers/dropdown_controller.js || true  # 0 import specifiers
grep -c "@hotwired/stimulus" packages/support/assets/dist/controllers/dropdown_controller.js    # >= 1 (still an external import)
```

- [ ] **Step 5: Update CLAUDE.md**

In `CLAUDE.md` Commands (JS/CSS): amend the `support` build sentence to note the dropdown controller is built with `--bundle` (inlining `@floating-ui/dom`, Stimulus external) while all other files build per-file. In Conventions (TS bullet): after the Stimulus peer-dep sentence, add that `@floating-ui/dom` is a regular dependency bundled into dist, never resolved by consumers.

- [ ] **Step 6: Commit**

```bash
git add packages/support/assets/src/controllers/dropdown_controller.ts packages/support/assets/package.json pnpm-lock.yaml packages/support/assets/dist CLAUDE.md
git commit -m "feat(support): dropdown Stimulus controller with bundled @floating-ui/dom

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

Also flip the controller row in `docs/progress/support/dropdown.md` to **done** and amend it into this commit (`git add docs/progress/support/dropdown.md && git commit --amend --no-edit`).

---

### Task 4: ActionGroup model core + `BelongsToGroup`

**Files:**
- Create: `packages/actions/src/ActionGroup.php`
- Create: `packages/actions/src/Concerns/BelongsToGroup.php`
- Modify: `packages/actions/src/Action.php` (add `use BelongsToGroup;`)
- Test: `packages/actions/tests/Unit/ActionGroupTest.php`, `packages/actions/tests/Unit/Concerns/BelongsToGroupTest.php`
- Create: `docs/progress/actions/action-group.md`; Modify: `docs/progress/actions/README.md` (row link)

**Interfaces:**
- Consumes: `Action::getName(): string` (throws on blank — the unnamed-action guard comes free), actions `HasLabel` (`label()` setter; `getLabel()` overridden here), support `HasIcon::getIcon(?default)`, actions `HasSize`, support `HasColor`/`HasTooltip`/`HasExtraAttributes`, `Configurable::configure()` via `ViewComponent` inheritance.
- Produces: `ActionGroup::make(array $actions): static`, `actions(array): static`, `getActions(): array` (plain children for now — Task 6 adds template pushing), `getFlatActions(): array<string, Action>`, `getLabel(): string|Markup|null` defaulting to `'Actions'`; `BelongsToGroup::group(?ActionGroup): static` / `getGroup(): ?ActionGroup` on both `Action` and `ActionGroup`. Tasks 5–9 extend this class.

- [ ] **Step 1: Write the failing tests**

`packages/actions/tests/Unit/Concerns/BelongsToGroupTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

it('has no group by default', function (): void {
    expect(Action::make('edit')->getGroup())->toBeNull();
});

it('holds a group back-reference', function (): void {
    $group = ActionGroup::make([]);
    $action = Action::make('edit')->group($group);

    expect($action->getGroup())->toBe($group);
});
```

`packages/actions/tests/Unit/ActionGroupTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

it('keeps its actions and back-references each one', function (): void {
    $edit = Action::make('edit');
    $delete = Action::make('delete');
    $group = ActionGroup::make([$edit, $delete]);

    expect($group->getActions())->toBe([$edit, $delete])
        ->and($edit->getGroup())->toBe($group)
        ->and($delete->getGroup())->toBe($group);
});

it('builds an eager name-keyed flat map', function (): void {
    $edit = Action::make('edit');
    $delete = Action::make('delete');

    expect(ActionGroup::make([$edit, $delete])->getFlatActions())
        ->toBe(['edit' => $edit, 'delete' => $delete]);
});

it('recurses nested groups into the flat map', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $group = ActionGroup::make([$edit, ActionGroup::make([$archive, $delete])]);

    expect($group->getFlatActions())
        ->toBe(['edit' => $edit, 'archive' => $archive, 'delete' => $delete]);
});

it('rejects an unnamed action', function (): void {
    ActionGroup::make([new class(null) extends Action {}]);
})->throws(LogicException::class);

it('defaults its label to Actions and accepts an override', function (): void {
    expect(ActionGroup::make([])->getLabel())->toBe('Actions')
        ->and(ActionGroup::make([])->label('More')->getLabel())->toBe('More')
        ->and(ActionGroup::make([])->label(fn (): string => 'Extra')->getLabel())->toBe('Extra');
});

it('falls back to an ellipsis icon that an explicit icon overrides', function (): void {
    expect(ActionGroup::make([])->getIcon(ActionGroup::DEFAULT_ICON))->toBe('heroicons:ellipsis-vertical')
        ->and(ActionGroup::make([])->icon('heroicons:x-mark')->getIcon(ActionGroup::DEFAULT_ICON))->toBe('heroicons:x-mark');
});
```

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=ActionGroup`
Expected: FAIL — `Class "Sylament\Actions\ActionGroup" not found`.

- [ ] **Step 3: Implement**

`packages/actions/src/Concerns/BelongsToGroup.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Sylament\Actions\ActionGroup;

trait BelongsToGroup
{
    protected ?ActionGroup $group = null;

    public function group(?ActionGroup $group = null): static
    {
        $this->group = $group;

        return $this;
    }

    public function getGroup(): ?ActionGroup
    {
        return $this->group;
    }
}
```

`packages/actions/src/ActionGroup.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\HasLabel;
use Sylament\Actions\Concerns\HasSize;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasExtraAttributes;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Components\Concerns\HasIconSize;
use Sylament\Support\Components\Concerns\HasTooltip;
use Sylament\Support\Components\ViewComponent;
use Twig\Markup;

class ActionGroup extends ViewComponent
{
    use BelongsToGroup;
    use HasColor;
    use HasExtraAttributes;
    use HasIcon;
    use HasIconSize;
    use HasLabel;
    use HasSize;
    use HasTooltip;

    public const string DEFAULT_ICON = 'heroicons:ellipsis-vertical';

    protected string $evaluationIdentifier = 'group';

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $actions = [];

    /**
     * @var array<string, Action>
     */
    protected array $flatActions = [];

    /**
     * @param array<Action|ActionGroup> $actions
     */
    final public function __construct(array $actions)
    {
        $this->actions($actions);
    }

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public static function make(array $actions = []): static
    {
        $group = new static($actions);

        $group->configure();

        return $group;
    }

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = [];
        $this->flatActions = [];

        foreach ($actions as $action) {
            $action->group($this);

            if ($action instanceof ActionGroup) {
                $this->flatActions = [...$this->flatActions, ...$action->getFlatActions()];
            } else {
                $this->flatActions[$action->getName()] = $action;
            }

            $this->actions[] = $action;
        }

        return $this;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return array<string, Action>
     */
    public function getFlatActions(): array
    {
        return $this->flatActions;
    }

    public function getLabel(): string|Markup|null
    {
        return $this->evaluate($this->label) ?? 'Actions';
    }
}
```

Note: the class-level `getLabel()` wins over the `HasLabel` trait method (PHP precedence), dropping the name-derived default — a group has no name. `Closure` is imported for the `label()` union inherited from the trait; if PHP-CS-Fixer flags it unused, remove the import.

In `packages/actions/src/Action.php`, add `use BelongsToGroup;` to the trait list (alphabetical position: first) and its import.

- [ ] **Step 4: Run to verify they pass**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=ActionGroup`
Expected: PASS. Then `composer test:actions` and `composer test:static` → green.

- [ ] **Step 5: Start the ledger**

Create `docs/progress/actions/action-group.md` following `docs/progress/actions/action.md`'s structure (heading, prose, Status: in progress, Reference links to `vendor/filament/actions/src/ActionGroup.php`, Surface table, Details, Divergences). Rows: model core (**done**), `BelongsToGroup` (**done**), label default (**done**, diverges: plain string `'Actions'`, no translator), `HasDropdown` (todo → Task 5), visibility composition (todo → Task 6), propagation (todo → Task 6), grouped item template (todo → Task 7), trigger machinery (todo → Task 8), rendering (todo → Task 9), and **deferred** rows: badge/button-group/grouped/link triggers, submenu rendering, teleport/auto-placement, `toArray`/`Arrayable`, table/bulk concerns. Add the row link in `docs/progress/actions/README.md`.

- [ ] **Step 6: Format + commit**

```bash
composer format
git add packages/actions/src/ActionGroup.php packages/actions/src/Concerns/BelongsToGroup.php packages/actions/src/Action.php packages/actions/tests/Unit/ActionGroupTest.php packages/actions/tests/Unit/Concerns/BelongsToGroupTest.php docs/progress/actions/action-group.md docs/progress/actions/README.md
git commit -m "feat(actions): ActionGroup model core with eager flat-actions map

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 5: `HasDropdown` concern

**Files:**
- Create: `packages/actions/src/Concerns/HasDropdown.php`
- Modify: `packages/actions/src/ActionGroup.php` (`use HasDropdown;`)
- Test: `packages/actions/tests/Unit/Concerns/HasDropdownTest.php`
- Create: `packages/actions/tests/Fixtures/Concerns/HasDropdownFixture.php`
- Modify: `docs/progress/actions/action-group.md` (flip the row)

**Interfaces:**
- Consumes: `EvaluatesClosures::evaluate()`.
- Produces: `dropdown(bool|Closure $condition = true)`, `hasDropdown(): bool` (default `true`); `dropdownPlacement()`, `defaultDropdownPlacement()`, `getDropdownPlacement(): ?string` (explicit ?? default); `dropdownOffset()` / `getDropdownOffset(): ?int`; `dropdownWidth()` / `getDropdownWidth(): ?string`; `dropdownMaxHeight()` / `getDropdownMaxHeight(): ?string`; `dropdownFlip(bool|Closure $condition = true)` / `hasDropdownFlip(): bool` (default `true`). Task 9's template feeds these into the Dropdown component props (null placement/offset → the component's own defaults).

- [ ] **Step 1: Write the failing tests**

`packages/actions/tests/Fixtures/Concerns/HasDropdownFixture.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasDropdown;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasDropdownFixture
{
    use EvaluatesClosures;
    use HasDropdown;
}
```

`packages/actions/tests/Unit/Concerns/HasDropdownTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasDropdownFixture;

it('has an unconfigured dropdown by default', function (): void {
    $fixture = new HasDropdownFixture();

    expect($fixture->hasDropdown())->toBeTrue()
        ->and($fixture->hasDropdownFlip())->toBeTrue()
        ->and($fixture->getDropdownPlacement())->toBeNull()
        ->and($fixture->getDropdownOffset())->toBeNull()
        ->and($fixture->getDropdownWidth())->toBeNull()
        ->and($fixture->getDropdownMaxHeight())->toBeNull();
});

it('stores and evaluates dropdown configuration', function (): void {
    $fixture = new HasDropdownFixture()
        ->dropdown(fn (): bool => false)
        ->dropdownPlacement('top-end')
        ->dropdownOffset(fn (): int => 4)
        ->dropdownWidth('16rem')
        ->dropdownMaxHeight('20rem')
        ->dropdownFlip(false);

    expect($fixture->hasDropdown())->toBeFalse()
        ->and($fixture->getDropdownPlacement())->toBe('top-end')
        ->and($fixture->getDropdownOffset())->toBe(4)
        ->and($fixture->getDropdownWidth())->toBe('16rem')
        ->and($fixture->getDropdownMaxHeight())->toBe('20rem')
        ->and($fixture->hasDropdownFlip())->toBeFalse();
});

it('falls back from explicit placement to the default placement', function (): void {
    $fixture = new HasDropdownFixture()->defaultDropdownPlacement('right-start');

    expect($fixture->getDropdownPlacement())->toBe('right-start')
        ->and($fixture->dropdownPlacement('left-start')->getDropdownPlacement())->toBe('left-start');
});
```

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=HasDropdownTest`
Expected: FAIL — trait not found.

- [ ] **Step 3: Implement**

`packages/actions/src/Concerns/HasDropdown.php` (port of Filament's `Actions\Concerns\HasDropdown` minus teleport/auto-placement; `dropdownWidth` is `string` — no `Width` enum exists in support, matching Modal's string widths):

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

/**
 * Ports Filament's actions `HasDropdown`. `dropdownTeleport()` and
 * `dropdownAutoPlacement()` are deferred with the dropdown features they
 * configure; `dropdownWidth()` takes a CSS length string — support has no
 * `Width` enum, and the value flows into a `--sy-dropdown-width` variable.
 */
trait HasDropdown
{
    protected bool|Closure $hasDropdown = true;

    protected string|Closure|null $dropdownPlacement = null;

    protected string|Closure|null $defaultDropdownPlacement = null;

    protected string|Closure|null $dropdownMaxHeight = null;

    protected int|Closure|null $dropdownOffset = null;

    protected string|Closure|null $dropdownWidth = null;

    protected bool|Closure $hasDropdownFlip = true;

    public function dropdown(bool|Closure $condition = true): static
    {
        $this->hasDropdown = $condition;

        return $this;
    }

    public function dropdownPlacement(string|Closure|null $placement): static
    {
        $this->dropdownPlacement = $placement;

        return $this;
    }

    public function defaultDropdownPlacement(string|Closure|null $placement): static
    {
        $this->defaultDropdownPlacement = $placement;

        return $this;
    }

    public function dropdownMaxHeight(string|Closure|null $height): static
    {
        $this->dropdownMaxHeight = $height;

        return $this;
    }

    public function dropdownOffset(int|Closure|null $offset): static
    {
        $this->dropdownOffset = $offset;

        return $this;
    }

    public function dropdownWidth(string|Closure|null $width): static
    {
        $this->dropdownWidth = $width;

        return $this;
    }

    public function dropdownFlip(bool|Closure $condition = true): static
    {
        $this->hasDropdownFlip = $condition;

        return $this;
    }

    public function hasDropdown(): bool
    {
        return (bool) $this->evaluate($this->hasDropdown);
    }

    public function getDropdownPlacement(): ?string
    {
        return $this->evaluate($this->dropdownPlacement) ?? $this->evaluate($this->defaultDropdownPlacement);
    }

    public function getDropdownMaxHeight(): ?string
    {
        return $this->evaluate($this->dropdownMaxHeight);
    }

    public function getDropdownOffset(): ?int
    {
        return $this->evaluate($this->dropdownOffset);
    }

    public function getDropdownWidth(): ?string
    {
        return $this->evaluate($this->dropdownWidth);
    }

    public function hasDropdownFlip(): bool
    {
        return (bool) $this->evaluate($this->hasDropdownFlip);
    }
}
```

Add `use HasDropdown;` (+ import) to `ActionGroup`.

- [ ] **Step 4: Run to verify they pass**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=HasDropdownTest`
Expected: PASS. `composer test:static` → green.

- [ ] **Step 5: Flip the ledger row, format + commit**

Flip the `HasDropdown` row to **done** in `docs/progress/actions/action-group.md` (divergence note: string widths, deferred teleport/auto-placement).

```bash
composer format
git add packages/actions/src/Concerns/HasDropdown.php packages/actions/src/ActionGroup.php packages/actions/tests/Unit/Concerns/HasDropdownTest.php packages/actions/tests/Fixtures/Concerns/HasDropdownFixture.php docs/progress/actions/action-group.md
git commit -m "feat(actions): HasDropdown configuration concern on ActionGroup

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 6: Visibility composition, propagation, and grouped-template pushing

**Files:**
- Modify: `packages/actions/src/ActionGroup.php` (visibility, `liveComponent()`/`record()` forwarding, `getActions()` template pushing)
- Modify: `packages/actions/src/Action.php` (add `GROUPED_TEMPLATE` const; `getRecord()` group fallback in `packages/actions/src/Concerns/InteractsWithRecord.php`)
- Test: extend `packages/actions/tests/Unit/ActionGroupTest.php`
- Modify: `docs/progress/actions/action-group.md`

**Interfaces:**
- Consumes: actions `CanBeHidden` (`isHidden()`/`isVisible()`, `packages/actions/src/Concerns/CanBeHidden.php`), `BelongsToLiveComponent::liveComponent()`, `InteractsWithRecord::record()`/`getRecord()`, Task 1's `defaultTemplate()`.
- Produces: `ActionGroup::isHidden(): bool` (own flag OR all children hidden), `liveComponent()`/`record()` overrides forwarding to flat actions, `Action::GROUPED_TEMPLATE = '@SylamentActions/components/grouped-action.html.twig'`, `getActions()` pushing that template as default when `hasDropdown()`. `Action::getRecord()` falls back to the group's record.

- [ ] **Step 1: Write the failing tests** (append to `ActionGroupTest.php`)

```php
it('is hidden when every child is hidden', function (): void {
    $group = ActionGroup::make([Action::make('edit')->hidden(), Action::make('delete')->hidden()]);

    expect($group->isHidden())->toBeTrue()
        ->and($group->isVisible())->toBeFalse();
});

it('is visible while any child is visible', function (): void {
    expect(ActionGroup::make([Action::make('edit')->hidden(), Action::make('delete')])->isHidden())
        ->toBeFalse();
});

it('hides on its own flag even with visible children', function (): void {
    expect(ActionGroup::make([Action::make('edit')])->hidden()->isHidden())->toBeTrue();
});

it('is hidden when empty', function (): void {
    expect(ActionGroup::make([])->isHidden())->toBeTrue();
});

it('forwards the live component to nested flat actions', function (): void {
    $liveComponent = new \Sylament\Actions\Tests\Fixtures\Twig\Components\ActionHost();
    $delete = Action::make('delete');
    $group = ActionGroup::make([Action::make('edit'), ActionGroup::make([$delete])]);

    $group->liveComponent($liveComponent);

    expect($delete->getLiveComponent())->toBe($liveComponent);
});

it('forwards the record and lets actions fall back to it', function (): void {
    $record = new \stdClass();
    $edit = Action::make('edit');

    ActionGroup::make([$edit])->record($record);

    expect($edit->getRecord())->toBe($record);
});

it('lets an action record win over the group record', function (): void {
    $own = new \stdClass();
    $edit = Action::make('edit')->record($own);

    ActionGroup::make([$edit])->record(new \stdClass());

    expect($edit->getRecord())->toBe($own);
});

it('pushes the grouped item template onto action children as a default', function (): void {
    $edit = Action::make('edit');
    ActionGroup::make([$edit])->getActions();

    expect($edit->getTemplate())->toBe(Action::GROUPED_TEMPLATE);
});

it('keeps the normal action template under dropdown(false)', function (): void {
    $edit = Action::make('edit');
    ActionGroup::make([$edit])->dropdown(false)->getActions();

    expect($edit->getTemplate())->toBe('@SylamentActions/components/button-action.html.twig');
});

it('never clobbers an explicit action template', function (): void {
    $edit = Action::make('edit')->template('custom.html.twig');
    ActionGroup::make([$edit])->getActions();

    expect($edit->getTemplate())->toBe('custom.html.twig');
});
```

Note: `ActionHost` is the existing live-component fixture (`packages/actions/tests/Fixtures/Twig/Components/ActionHost.php`); it has no constructor and constructs bare, so instantiating it in a Unit test is fine.

- [ ] **Step 2: Run to verify the new tests fail**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=ActionGroupTest`
Expected: new tests FAIL (`isHidden` undefined on group / template not pushed).

- [ ] **Step 3: Implement**

On `Action` (`packages/actions/src/Action.php`), add next to the `$template` property:

```php
    public const string GROUPED_TEMPLATE = '@SylamentActions/components/grouped-action.html.twig';
```

In `packages/actions/src/Concerns/InteractsWithRecord.php`, change `getRecord()` (the trait is used by `Action`, which also has `BelongsToGroup`; `method_exists` guards the fixture class that uses the trait standalone):

```php
    public function getRecord(): mixed
    {
        $record = $this->evaluate($this->record);

        if ($record !== null) {
            return $record;
        }

        if (method_exists($this, 'getGroup')) {
            return $this->getGroup()?->getRecord();
        }

        return null;
    }
```

On `ActionGroup`, add `use CanBeHidden;` and `use BelongsToLiveComponent;` and `use InteractsWithRecord;` (+ imports), then the overrides:

```php
    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        if (! $this->evaluate($this->isVisible)) {
            return true;
        }

        foreach ($this->actions as $action) {
            if ($action->isVisible()) {
                return false;
            }
        }

        return true;
    }

    public function liveComponent(?InteractsWithActions $liveComponent = null): static
    {
        $this->liveComponent = $liveComponent;

        foreach ($this->actions as $action) {
            $action->liveComponent($liveComponent);
        }

        return $this;
    }
```

(`record()` is NOT overridden — the trait setter suffices, because children reach the group's record through the `getRecord()` group fallback, not through forwarding.)

Replace `getActions()`:

```php
    /**
     * @return array<Action|ActionGroup>
     */
    public function getActions(): array
    {
        $grouped = $this->hasDropdown();

        foreach ($this->actions as $action) {
            if ($action instanceof Action) {
                $action->defaultTemplate($grouped ? Action::GROUPED_TEMPLATE : null);
            }
        }

        return $this->actions;
    }
```

`import` list: `Sylament\Actions\Contracts\InteractsWithActions`.

- [ ] **Step 4: Run to verify everything passes**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests` then `composer test:static`
Expected: PASS / green. (If the `ActionHost` fixture needs constructor args, adjust per the Step 1 note before moving on.)

- [ ] **Step 5: Ledger, format + commit**

Flip visibility + propagation rows to **done** in `docs/progress/actions/action-group.md` (divergences: no `isHiddenInGroup()` — children checked via plain `isHidden()`; no table visibility cache).

```bash
composer format
git add packages/actions/src/ActionGroup.php packages/actions/src/Action.php packages/actions/src/Concerns/InteractsWithRecord.php packages/actions/tests/Unit/ActionGroupTest.php docs/progress/actions/action-group.md
git commit -m "feat(actions): ActionGroup visibility composition and context propagation

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 7: Grouped item template

**Files:**
- Create: `packages/actions/templates/components/grouped-action.html.twig`
- Test: `packages/actions/tests/Integration/GroupedActionRenderTest.php`
- Modify: `docs/progress/actions/action-group.md`

**Interfaces:**
- Consumes: `Action` getters used by `button-action.html.twig` (`getUrl`, `shouldOpenUrlInNewTab`, `isDisabled`, `getIcon`, `getIconSize`, `getLabel`, `getName`, `getTooltip`, `getExtraAttributes`), `attrs()`/`classes()`/`generateIconHtml()` Twig helpers, `live_action()` (ux-live-component), `.sy-dropdown-item` CSS from Task 2.
- Produces: the template at `Action::GROUPED_TEMPLATE`, rendering one full-width dropdown row per action.

- [ ] **Step 1: Write the failing render test**

`packages/actions/tests/Integration/GroupedActionRenderTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

function renderGroupedAction(Action $action): string
{
    return (string) $action->render(test()->getContainer()->get('twig'));
}

it('renders a grouped action as a dropdown item row wired to mountAction', function (): void {
    $delete = Action::make('delete')->icon('heroicons:x-mark');
    ActionGroup::make([$delete])->getActions();

    $html = renderGroupedAction($delete);

    expect($html)->toContain('sy sy-dropdown-item sy-ac')
        ->toContain('<svg')
        ->toContain('Delete')
        ->toContain('type="button"')
        ->toContain('data-action="live#action"')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"');
});

it('renders a url grouped action as a link without mount wiring', function (): void {
    $edit = Action::make('edit')->url('/articles/1/edit');
    ActionGroup::make([$edit])->getActions();

    $html = renderGroupedAction($edit);

    expect($html)->toContain('<a')
        ->toContain('href="/articles/1/edit"')
        ->not->toContain('live#action')
        ->not->toContain('type=');
});

it('renders a disabled grouped action', function (): void {
    $delete = Action::make('delete')->disabled();
    ActionGroup::make([$delete])->getActions();

    $html = renderGroupedAction($delete);

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=GroupedActionRenderTest`
Expected: FAIL — Twig `LoaderError`, template `grouped-action.html.twig` not found.

- [ ] **Step 3: Write the template**

`packages/actions/templates/components/grouped-action.html.twig` (modeled on `button-action.html.twig`, minus size/color trigger styling — items are uniform rows):

```twig
{% set tag = component.getUrl ? 'a' : 'button' %}
{% set iconHtml = component.getIcon ? generateIconHtml(component.getIcon, null, null, component.getIconSize ?? 'sm') : null %}

<{{ tag }}
    {{ attrs({
        class: classes('sy sy-dropdown-item sy-ac', {
            'sy-disabled': component.isDisabled,
        }),
        type: component.getUrl ? false : 'button',
        disabled: component.isDisabled and not component.getUrl,
        'aria-disabled': component.isDisabled ? 'true' : false,
        href: component.getUrl ?: false,
        target: component.shouldOpenUrlInNewTab ? '_blank' : false,
        title: component.getTooltip ?: false,
    }, component.getExtraAttributes) }}
    {%- if not component.getUrl %} {{ live_action('mountAction', { name: component.getName }) }}{% endif %}
>
    {{- iconHtml -}}
    <span class="sy-dropdown-item-label">{{ component.getLabel ?? component.getName }}</span>
</{{ tag }}>
```

Add to `packages/support/assets/src/styles/components/dropdown.css`:

```css
.sy-dropdown-item-label {
    @apply grow truncate;
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=GroupedActionRenderTest`
Expected: PASS. Run `pnpm build` (CSS addition compiles; dist updated).

- [ ] **Step 5: Ledger, format + commit**

Flip the grouped-item row to **done**.

```bash
composer format
git add packages/actions/templates/components/grouped-action.html.twig packages/actions/tests/Integration/GroupedActionRenderTest.php packages/support/assets/src/styles/components/dropdown.css packages/support/assets/dist docs/progress/actions/action-group.md
git commit -m "feat(actions): grouped-action dropdown item template

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 8: Trigger-template machinery

**Files:**
- Modify: `packages/actions/src/ActionGroup.php`
- Create: `packages/actions/templates/components/action-group/icon-button-trigger.html.twig`, `packages/actions/templates/components/action-group/button-trigger.html.twig`
- Test: extend `packages/actions/tests/Unit/ActionGroupTest.php`; Create: `packages/actions/tests/Integration/ActionGroupTriggerRenderTest.php`
- Modify: `docs/progress/actions/action-group.md`

**Interfaces:**
- Consumes: `<twig:SylamentSupport:IconButton>` props (`icon`, `iconAlias`, `label`, `color`, `size`, `disabled`) and `<twig:SylamentSupport:Button>` (content block + `icon`, `color`, `size`), `ActionGroup::DEFAULT_ICON`, `getLabel()`, `getSize()`, `getColor()`.
- Produces: `triggerTemplate()`, `defaultTriggerTemplate()`, `getTriggerTemplate(): string`, `iconButton()`, `button()`, `isIconButton()`, `isButton()`, consts `ICON_BUTTON_TEMPLATE` / `BUTTON_TEMPLATE`; `setUp()` seeding `defaultTriggerTemplate(static::ICON_BUTTON_TEMPLATE)`. Task 9 includes `component.getTriggerTemplate` inside the group template.

- [ ] **Step 1: Write the failing unit tests** (append to `ActionGroupTest.php`)

```php
it('defaults to the icon-button trigger template', function (): void {
    $group = ActionGroup::make([]);

    expect($group->getTriggerTemplate())->toBe(ActionGroup::ICON_BUTTON_TEMPLATE)
        ->and($group->isIconButton())->toBeTrue()
        ->and($group->isButton())->toBeFalse();
});

it('switches to the button trigger template', function (): void {
    $group = ActionGroup::make([])->button();

    expect($group->getTriggerTemplate())->toBe(ActionGroup::BUTTON_TEMPLATE)
        ->and($group->isButton())->toBeTrue()
        ->and($group->isIconButton())->toBeFalse();
});

it('lets an explicit trigger template win over the default', function (): void {
    $group = ActionGroup::make([])->triggerTemplate('custom-trigger.html.twig');

    expect($group->getTriggerTemplate())->toBe('custom-trigger.html.twig')
        ->and($group->isIconButton())->toBeFalse();
});
```

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=ActionGroupTest`
Expected: new tests FAIL — undefined constant/method.

- [ ] **Step 3: Implement the machinery**

On `ActionGroup`:

```php
    public const string ICON_BUTTON_TEMPLATE = '@SylamentActions/components/action-group/icon-button-trigger.html.twig';

    public const string BUTTON_TEMPLATE = '@SylamentActions/components/action-group/button-trigger.html.twig';

    protected string|Closure|null $triggerTemplate = null;

    protected string|Closure|null $defaultTriggerTemplate = null;
```

```php
    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultTriggerTemplate(static::ICON_BUTTON_TEMPLATE);
    }

    public function triggerTemplate(string|Closure|null $template): static
    {
        $this->triggerTemplate = $template;

        return $this;
    }

    public function defaultTriggerTemplate(string|Closure|null $template): static
    {
        $this->defaultTriggerTemplate = $template;

        return $this;
    }

    public function getTriggerTemplate(): string
    {
        return $this->evaluate($this->triggerTemplate)
            ?? $this->evaluate($this->defaultTriggerTemplate)
            ?? static::ICON_BUTTON_TEMPLATE;
    }

    public function iconButton(): static
    {
        return $this->triggerTemplate(static::ICON_BUTTON_TEMPLATE);
    }

    public function button(): static
    {
        return $this->triggerTemplate(static::BUTTON_TEMPLATE);
    }

    public function isIconButton(): bool
    {
        return $this->getTriggerTemplate() === static::ICON_BUTTON_TEMPLATE;
    }

    public function isButton(): bool
    {
        return $this->getTriggerTemplate() === static::BUTTON_TEMPLATE;
    }
```

Note: `setUp()` requires `parent::setUp()` to exist up the `Component` hierarchy — it does (`Configurable`); mirror `Action`'s own `setUp()` handling if `Action` defines one.

- [ ] **Step 4: Write the trigger templates**

`packages/actions/templates/components/action-group/icon-button-trigger.html.twig`:

```twig
<twig:SylamentSupport:IconButton
    :icon="group.getIcon(constant('Sylament\\Actions\\ActionGroup::DEFAULT_ICON'))"
    :label="group.getLabel"
    :color="group.getColor ?? 'gray'"
    :size="group.getSize ?? 'md'"
/>
```

`packages/actions/templates/components/action-group/button-trigger.html.twig`:

```twig
<twig:SylamentSupport:Button
    :icon="group.getIcon"
    :color="group.getColor ?? 'gray'"
    :size="group.getSize ?? 'md'"
>{{ group.getLabel }}</twig:SylamentSupport:Button>
```

If `getSize()`/`getColor()` return non-null defaults already (check `HasSize::getSize()` / `HasColor::getColor()` bodies while implementing), drop the `?? …` fallbacks accordingly — the Twig `??` on a method returning a default would be dead code.

- [ ] **Step 5: Write the failing trigger render test, then run it**

`packages/actions/tests/Integration/ActionGroupTriggerRenderTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\ActionGroup;

function renderTrigger(ActionGroup $group): string
{
    return test()->getContainer()->get('twig')->render($group->getTriggerTemplate(), ['group' => $group]);
}

it('renders the default icon-button trigger with the ellipsis icon and an accessible label', function (): void {
    $html = renderTrigger(ActionGroup::make([]));

    expect($html)->toContain('sy sy-icon-btn')
        ->toContain('<svg')
        ->toContain('aria-label="Actions"');
});

it('renders the labeled button trigger', function (): void {
    $html = renderTrigger(ActionGroup::make([])->button()->label('More'));

    expect($html)->toContain('sy sy-btn')
        ->toContain('More');
});
```

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=ActionGroupTriggerRenderTest`
Expected: FAIL first (templates missing) if run before Step 4 — order Steps 4↔5 TDD-style if preferred; both must PASS now. The ellipsis test needs a fixture icon: add `packages/actions/tests/Fixtures/assets/icons/heroicons/ellipsis-vertical.svg` (copy any existing fixture SVG's structure, e.g. `x-mark.svg`).

- [ ] **Step 6: Full suite, ledger, format + commit**

Run: `composer test:actions && composer test:static` → green. Flip the trigger-machinery row to **done** (deferred rows for badge/button-group/grouped/link triggers stay).

```bash
composer format
git add packages/actions/src/ActionGroup.php packages/actions/templates/components/action-group packages/actions/tests/Unit/ActionGroupTest.php packages/actions/tests/Integration/ActionGroupTriggerRenderTest.php packages/actions/tests/Fixtures/assets/icons/heroicons/ellipsis-vertical.svg docs/progress/actions/action-group.md
git commit -m "feat(actions): ActionGroup trigger-template machinery with icon-button and button triggers

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 9: ActionGroup rendering

**Files:**
- Create: `packages/actions/templates/components/action-group.html.twig`
- Modify: `packages/actions/src/ActionGroup.php` (`$template` default + nested-render guard)
- Test: `packages/actions/tests/Integration/ActionGroupRenderTest.php`
- Modify: `docs/progress/actions/action-group.md`

**Interfaces:**
- Consumes: Task 2's `<twig:SylamentSupport:Dropdown>` props, Task 5 getters, Task 6 `getActions()`, Task 8 `getTriggerTemplate()`, `renderComponent()` Twig function (support `ComponentExtension`).
- Produces: `ActionGroup::render()` — the complete dropdown (or inline row) — used later by schema render loops and host templates.

- [ ] **Step 1: Write the failing render tests**

`packages/actions/tests/Integration/ActionGroupRenderTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

function renderGroup(ActionGroup $group): string
{
    return (string) $group->render(test()->getContainer()->get('twig'));
}

it('renders the dropdown with trigger and grouped items', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit'), Action::make('delete')]));

    expect($html)->toContain('sy sy-dropdown')
        ->toContain('data-controller="sylament--support--dropdown"')
        ->toContain('sy sy-icon-btn')
        ->toContain('sy-dropdown-item')
        ->toContain('data-live-name-param="edit"')
        ->toContain('data-live-name-param="delete"');
});

it('feeds dropdown configuration into the primitive', function (): void {
    $html = renderGroup(
        ActionGroup::make([Action::make('edit')])
            ->dropdownPlacement('top-end')
            ->dropdownOffset(4)
            ->dropdownWidth('16rem'),
    );

    expect($html)->toContain('data-sylament--support--dropdown-placement-value="top-end"')
        ->toContain('data-sylament--support--dropdown-offset-value="4"')
        ->toContain('--sy-dropdown-width: 16rem;');
});

it('skips hidden items', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit'), Action::make('delete')->hidden()]));

    expect($html)->toContain('data-live-name-param="edit"')
        ->not->toContain('data-live-name-param="delete"');
});

it('renders an inline row under dropdown(false)', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit')])->dropdown(false));

    expect($html)->toContain('sy-ac-group')
        ->toContain('sy sy-btn sy-ac')
        ->not->toContain('sy-dropdown-panel');
});

it('refuses to render a nested group', function (): void {
    renderGroup(ActionGroup::make([ActionGroup::make([Action::make('edit')])]));
})->throws(LogicException::class, 'Submenu');
```

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=ActionGroupRenderTest`
Expected: FAIL — group template null (`render()` returns null → `(string) null` empty).

- [ ] **Step 3: Implement**

On `ActionGroup`, set the template default and the fail-fast guard:

```php
    protected string|Closure|null $template = '@SylamentActions/components/action-group.html.twig';
```

```php
    public function render(Environment $twig): ?string
    {
        if ($this->getGroup() !== null) {
            throw new LogicException('Submenu rendering is not supported yet: an ActionGroup cannot be rendered inside another ActionGroup.');
        }

        return parent::render($twig);
    }
```

(+ imports `LogicException`, `Twig\Environment`.)

`packages/actions/templates/components/action-group.html.twig`:

```twig
{% if component.hasDropdown %}
    <twig:SylamentSupport:Dropdown
        :placement="component.getDropdownPlacement ?? 'bottom-start'"
        :offset="component.getDropdownOffset ?? 8"
        :flip="component.hasDropdownFlip"
        :width="component.getDropdownWidth"
        :maxHeight="component.getDropdownMaxHeight"
    >
        <twig:block name="trigger">
            {% include component.getTriggerTemplate with { group: component } only %}
        </twig:block>

        {% for action in component.getActions|filter(action => action.isVisible) %}
            {{ renderComponent(action) }}
        {% endfor %}
    </twig:SylamentSupport:Dropdown>
{% else %}
    <div class="sy sy-ac-group">
        {% for action in component.getActions|filter(action => action.isVisible) %}
            {{ renderComponent(action) }}
        {% endfor %}
    </div>
{% endif %}
```

Add to `packages/actions/assets/src/styles/index.css` (the aggregator that support's build imports) a new component file import, and create `packages/actions/assets/src/styles/components/action-group.css`:

```css
@import './components/action-group.css' layer(components);
```

```css
.sy-ac-group {
    @apply inline-flex items-center gap-2;
}
```

- [ ] **Step 4: Run to verify they pass**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter=ActionGroupRenderTest`
Expected: PASS. Then `pnpm build` (actions CSS flows through support's build; commit refreshed dist) and the full `composer test`.

- [ ] **Step 5: Ledger, format + commit**

Flip the rendering row to **done**; the ledger's remaining open rows are the deferred set. Update `docs/progress/actions/action-group.md` status line to reflect the base being complete.

```bash
composer format
git add packages/actions/templates/components/action-group.html.twig packages/actions/src/ActionGroup.php packages/actions/tests/Integration/ActionGroupRenderTest.php packages/actions/assets/src/styles packages/support/assets/dist docs/progress/actions/action-group.md
git commit -m "feat(actions): render ActionGroup through the support Dropdown primitive

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 10: Roadmap, final gates, self-review

**Files:**
- Modify: `docs/progress/roadmap.md`
- Verify: everything

**Interfaces:**
- Consumes: all prior tasks.
- Produces: a recorded epic and a fully green tree.

- [ ] **Step 1: Record the pulled-forward epic in the roadmap**

In `docs/progress/roadmap.md`, change the `## Now` section to point at the next epic and add a pulled-forward block between `## Now` and `## Milestones`:

```markdown
## Now

**Pulled forward · 2 — schemas↔actions integration.** The next epic to pick up.

## Pulled forward

Epics taken out of milestone order by decision — recorded here so the thin-path
story stays honest. Spec for both:
`docs/superpowers/specs/2026-07-27-action-group-base-design.md`.

| # | Epic | Packages | Delivers | Status |
| --- | --- | --- | --- | --- |
| 1 | Dropdown primitive + ActionGroup base | [support](support/README.md), [actions](actions/README.md) | `<twig:SylamentSupport:Dropdown>` with a bundled Floating-UI Stimulus controller; `ActionGroup` model (eager `getFlatActions()`, visibility composition, propagation), icon-button/button triggers, grouped item rendering. Trigger-view catalog, submenus, badges, table/bulk deferred. | done |
| 2 | Schemas↔actions integration | [schemas](schemas/README.md), [actions](actions/README.md) | Actions as schema children, `prepareAction`/`prepareActionGroup`, `Schema::getAction()`, Section header/footer actions — flips the three blocked Section rows. | todo |
```

(Adjust the M1 out-of-scope sentence that says header/footer actions "wait on the schemas Actions integration" only if it now reads stale — it stays true until epic 2 lands.)

- [ ] **Step 2: Full verification battery**

Run each and confirm the stated result:

- `composer test` → green (all bundles' Pest + Rector dry-run + PHPStan max)
- `composer test:lint` → green
- `pnpm build` → succeeds; `git status` shows no uncommitted `dist/` drift after the Task 9 commit
- `pnpm test:types` → green
- `pnpm test:lint` → green

- [ ] **Step 3: Self-review the diff**

Re-read end-to-end (not just the diff): `packages/actions/src/ActionGroup.php`, `packages/support/src/Components/ViewComponent.php`, `packages/support/assets/src/controllers/dropdown_controller.ts`, the three new Twig templates. Grep for callers of any signature changed (`getTemplate`, `getRecord`): `grep -rn "getTemplate()\|getRecord()" packages/*/src packages/*/templates`. Confirm the progress files' links resolve. Report findings before declaring done.

- [ ] **Step 4: Commit**

```bash
git add docs/progress/roadmap.md
git commit -m "docs(progress): record the pulled-forward Dropdown + ActionGroup epic

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```
