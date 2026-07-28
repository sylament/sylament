# Modal Chrome Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port Filament's modal chrome (slide-over, sticky panes, alignment, close button, close/autofocus/click-through toggles, extra attribute bags) end to end from `CanOpenModal` through support `Modal` props/CSS/Stimulus — roadmap epic 4, per the approved spec `docs/superpowers/specs/2026-07-28-modal-chrome-design.md`.

**Architecture:** The support `<dialog>` becomes a full-viewport overlay+centering container (paints the dim itself; `::backdrop` neutralized) with a new inner `div.sy-modal-window` as the visible box. PHP surface lands on `Sylament\Actions\Concerns\CanOpenModal` + two new traits with Filament-exact names/defaults; the Stimulus controller gains cancel/click-away/dismiss handling and a `dismissed` event that `ActionModal` maps to `unmountAction`. Transitions are CSS-only (`@starting-style` + `allow-discrete`).

**Tech Stack:** PHP 8.3+ Symfony bundles (Pest, PHPStan level max), Twig anonymous components (ux-twig-component), Stimulus 3 (TypeScript strict), Tailwind CSS 4.3 (`@apply`, `starting:` variant, `transition-discrete`).

## Global Constraints

- `declare(strict_types=1);` in every PHP file; PHPStan **level max** must stay green.
- Filament API names verbatim — no renames anywhere in this epic.
- Closure-taking setters: native `Closure` unions, direct assignment, **no param PHPDoc arity specs** (see CLAUDE.md "Closure typing convention").
- Semantic `sy-*` classes only in Twig — never Tailwind utilities in templates (template scanning is off).
- CSS: `@apply` + nesting in `packages/support/assets/src/styles/components/modal.css`; plain CSS only where `@apply` can't express it; dark mode via inline `dark:` variants; `components` cascade layer (already wired via the existing import).
- Pest: `$this::getContainer()` (never `static::`/`self::`); no explicit `bootKernel()`.
- Tests mirror `src/` paths: `Unit/Concerns/FooTest.php` for `src/Concerns/Foo.php`.
- Run `composer format` before every commit that touches PHP; never hand-format.
- Never edit `assets/dist` by hand; it is rebuilt once by `pnpm build` in Task 9 and committed there (CI gate compares dist to a fresh build).
- No new dependencies.
- Commit messages: conventional style matching history (`feat(actions): …`, `feat(support): …`, `test(actions): …`, `docs: …`) + trailer `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.

## Execution notes (lanes, worktrees, subagents)

Dependency DAG — two parallel lanes, then integration:

```
Lane A (support):    Task 1 → Task 2 → Task 6 → Task 7
Lane B (actions PHP):Task 3 → Task 4 → Task 5
Lane C (integration):            (A + B merged) → Task 8 → Task 9
```

- Task 6's tests live in the **actions** package (the Twig-component test kernel is there) but only exercise support templates + Task 1's enum — Lane A does not need Lane B.
- Worktrees (if parallelizing): `git worktree add` **then reset to the epic's base sha** (worktrees spawn from `main`, which may have moved); run `composer install` and `pnpm install` per worktree. The orchestrator owns the lane merge, the single dist rebuild (Task 9), and worktree cleanup.
- Suggested subagent model/effort per task: T1 sonnet/low · T2 sonnet/medium · T3–T5 sonnet/medium · T6 sonnet/high · T7 sonnet/high · T8 sonnet/high · T9 opus/medium (docs prose judgment). Review gates between tasks: strongest available model, high effort.
- Every task's final step is a commit; a lane is mergeable only with its bundle-scoped suite green (`composer test:support` / `composer test:actions`) plus `composer test:static`.

---

### Task 1: `SlideOverPosition` enum + close-button icon alias (support)

**Files:**
- Create: `packages/support/src/Enums/SlideOverPosition.php`
- Modify: `packages/support/src/Twig/SupportIconAlias.php`

**Interfaces:**
- Produces: `Sylament\Support\Enums\SlideOverPosition` (`Start = 'start'`, `End = 'end'`) — consumed by Task 3 (getter type) and Task 6 (template `value ?? raw`).
- Produces: `SupportIconAlias::MODAL_CLOSE_BUTTON = 'modal.close-button'` — consumed by Task 6.

- [ ] **Step 1: Create the enum** (mirrors `Alignment.php` style):

```php
<?php

declare(strict_types=1);

namespace Sylament\Support\Enums;

enum SlideOverPosition: string
{
    case Start = 'start';

    case End = 'end';
}
```

- [ ] **Step 2: Add the alias constant** to `SupportIconAlias` (after `SECTION_COLLAPSE_BUTTON`):

```php
    public const string MODAL_CLOSE_BUTTON = 'modal.close-button';
```

- [ ] **Step 3: Verify**

Run: `composer test:static && composer test:support`
Expected: green (declaration-only change; PHPStan is the verifier).

- [ ] **Step 4: Commit**

```bash
git add packages/support/src/Enums/SlideOverPosition.php packages/support/src/Twig/SupportIconAlias.php
git commit -m "feat(support): SlideOverPosition enum and modal close-button icon alias"
```

---

### Task 2: Stimulus modal controller chrome behaviors (support)

**Files:**
- Modify: `packages/support/assets/src/controllers/modal_controller.ts`

**Interfaces:**
- Produces (consumed by Task 6's template wiring): Stimulus values `closeByEscaping` (default true), `closeByClickingAway` (default true), `clickThrough` (default false); actions `dismiss`, `handleCancel` (for `cancel`), `trackPointerDown` (for `pointerdown`), `closeOnClickAway` (for `click`), `notifyDismissed` (for `close`); bubbling event `sylament--support--modal:dismissed` fired only for client-initiated closes (consumed by Task 8).

- [ ] **Step 1: Replace the controller** with:

```ts
import { Controller } from "@hotwired/stimulus";

export default class extends Controller<HTMLDialogElement> {
  static values = {
    id: String,
    open: Boolean,
    returnsFocus: { type: Boolean, default: true },
    closeByEscaping: { type: Boolean, default: true },
    closeByClickingAway: { type: Boolean, default: true },
    clickThrough: { type: Boolean, default: false },
  };

  declare readonly idValue: string;
  declare readonly openValue: boolean;
  declare readonly returnsFocusValue: boolean;
  declare readonly closeByEscapingValue: boolean;
  declare readonly closeByClickingAwayValue: boolean;
  declare readonly clickThroughValue: boolean;

  #opener: HTMLElement | null = null;

  #pointerDownOnBackdrop = false;

  #escapeListener: ((event: KeyboardEvent) => void) | null = null;

  /**
   * Server-driven open state, and the only path that works for a modal rendered by the
   * same response that asks it to open. A `sylament-open-modal` event dispatched then is
   * lost: the live controller morphs the DOM and dispatches synchronously, while Stimulus
   * binds listeners from a MutationObserver callback, so this element is not listening
   * yet. Stimulus invokes this callback on connect and on every later attribute change,
   * whenever that happens, so it cannot be raced.
   */
  openValueChanged(isOpen: boolean): void {
    if (isOpen) {
      this.#show();

      return;
    }

    if (this.element.open) {
      this.element.close();
    }
  }

  open(event: CustomEvent<{ id?: unknown }>): void {
    if (this.#matches(event)) {
      this.#show();
    }
  }

  close(event: CustomEvent<{ id?: unknown }>): void {
    if (this.#matches(event)) {
      this.element.close();
    }
  }

  dismiss(): void {
    this.element.close();
  }

  handleCancel(event: Event): void {
    if (!this.closeByEscapingValue) {
      event.preventDefault();
    }
  }

  trackPointerDown(event: PointerEvent): void {
    this.#pointerDownOnBackdrop = event.target === this.element;
  }

  /**
   * Closes on a backdrop click only when the press also began there: after a
   * text-selection drag that starts inside the window and releases outside it, the
   * `click` targets the common ancestor (this element), but the `pointerdown` did not.
   */
  closeOnClickAway(event: MouseEvent): void {
    if (this.closeByClickingAwayValue && this.#pointerDownOnBackdrop && event.target === this.element) {
      this.element.close();
    }

    this.#pointerDownOnBackdrop = false;
  }

  /**
   * Emitted only for client-initiated closes (ESC, click-away, close button): a
   * server-driven close flips the open value to false before calling close(), so the
   * value still being true here means the server does not know yet.
   */
  notifyDismissed(): void {
    this.#teardownEscapeListener();

    if (this.openValue) {
      this.dispatch("dismissed");
    }
  }

  restoreFocus(): void {
    if (this.returnsFocusValue) {
      this.#opener?.focus();
    }

    this.#opener = null;
  }

  disconnect(): void {
    this.#teardownEscapeListener();
  }

  #show(): void {
    if (this.element.open) {
      return;
    }

    this.#opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    if (this.clickThroughValue) {
      this.element.show();
      this.#setupEscapeListener();

      return;
    }

    this.element.showModal();
  }

  /**
   * A non-modal dialog (click-through uses show()) receives no `cancel` close request,
   * so Escape is reimplemented at the document level while it is open.
   */
  #setupEscapeListener(): void {
    if (!this.closeByEscapingValue || this.#escapeListener !== null) {
      return;
    }

    this.#escapeListener = (event: KeyboardEvent): void => {
      if (event.key === "Escape") {
        this.element.close();
      }
    };
    document.addEventListener("keydown", this.#escapeListener);
  }

  #teardownEscapeListener(): void {
    if (this.#escapeListener !== null) {
      document.removeEventListener("keydown", this.#escapeListener);
      this.#escapeListener = null;
    }
  }

  #matches(event: CustomEvent<{ id?: unknown }>): boolean {
    const eventId = event.detail?.id;
    const modalId = this.idValue !== "" ? this.idValue : this.element.id;

    return eventId != null && String(eventId) === modalId;
  }
}
```

- [ ] **Step 2: Verify types and lint**

Run: `pnpm test:types && pnpm test:lint`
Expected: green. (No JS test rig exists; behavior is asserted through rendered wiring in Task 6/8.)

- [ ] **Step 3: Commit**

```bash
git add packages/support/assets/src/controllers/modal_controller.ts
git commit -m "feat(support): modal controller chrome — cancel gate, click-away, dismiss, dismissed event, click-through show()"
```

---

### Task 3: `CanOpenModal` chrome toggles (actions)

**Files:**
- Modify: `packages/actions/src/Concerns/CanOpenModal.php`
- Test: `packages/actions/tests/Unit/Concerns/CanOpenModalTest.php`

**Interfaces:**
- Consumes: `Sylament\Support\Enums\SlideOverPosition` (Task 1). If running Lane B before Lane A merges, this import is the one cross-lane symbol — coordinate or cherry-pick Task 1 first (it is dependency-free).
- Produces (consumed by Tasks 4, 6, 8): `slideOver(bool|Closure $condition = true): static`, `isModalSlideOver(): bool`; `slideOverPosition(SlideOverPosition|Closure|null $position = null): static`, `getModalSlideOverPosition(): ?SlideOverPosition`; `stickyModalHeader(bool|Closure $condition = true): static`, `isModalHeaderSticky(): bool`; `stickyModalFooter(bool|Closure $condition = true): static`, `isModalFooterSticky(): bool`; `modalCloseButton(bool|Closure|null $condition = true): static`, `hasModalCloseButton(): bool`; `closeModalByClickingAway(bool|Closure|null $condition = true): static`, `isModalClosedByClickingAway(): bool`; `closeModalByEscaping(bool|Closure|null $condition = true): static`, `isModalClosedByEscaping(): bool`; `modalAutofocus(bool|Closure|null $condition = true): static`, `isModalAutofocused(): bool`; `modalClickThrough(bool|Closure|null $condition = true): static`, `isModalClickThrough(): bool`.

- [ ] **Step 1: Write the failing tests** — append to `CanOpenModalTest.php`:

```php
it('defaults the chrome toggles to Filament values', function (): void {
    $action = Action::make('delete');

    expect($action->isModalSlideOver())->toBeFalse()
        ->and($action->getModalSlideOverPosition())->toBeNull()
        ->and($action->isModalHeaderSticky())->toBeFalse()
        ->and($action->isModalFooterSticky())->toBeFalse()
        ->and($action->hasModalCloseButton())->toBeTrue()
        ->and($action->isModalClosedByClickingAway())->toBeTrue()
        ->and($action->isModalClosedByEscaping())->toBeTrue()
        ->and($action->isModalAutofocused())->toBeTrue()
        ->and($action->isModalClickThrough())->toBeFalse();
});

it('round-trips the chrome toggles, closures included', function (): void {
    $action = Action::make('delete')
        ->slideOver()
        ->slideOverPosition(SlideOverPosition::Start)
        ->modalCloseButton(false)
        ->closeModalByClickingAway(fn (): bool => false)
        ->closeModalByEscaping(false)
        ->modalAutofocus(false)
        ->modalClickThrough(fn (): bool => true);

    expect($action->isModalSlideOver())->toBeTrue()
        ->and($action->getModalSlideOverPosition())->toBe(SlideOverPosition::Start)
        ->and($action->hasModalCloseButton())->toBeFalse()
        ->and($action->isModalClosedByClickingAway())->toBeFalse()
        ->and($action->isModalClosedByEscaping())->toBeFalse()
        ->and($action->isModalAutofocused())->toBeFalse()
        ->and($action->isModalClickThrough())->toBeTrue();
});

it('defaults sticky header and footer to the slide-over state', function (): void {
    $slideOver = Action::make('delete')->slideOver();

    expect($slideOver->isModalHeaderSticky())->toBeTrue()
        ->and($slideOver->isModalFooterSticky())->toBeTrue()
        ->and($slideOver->stickyModalHeader(false)->isModalHeaderSticky())->toBeFalse()
        ->and($slideOver->stickyModalFooter(false)->isModalFooterSticky())->toBeFalse()
        ->and(Action::make('other')->stickyModalHeader()->isModalHeaderSticky())->toBeTrue();
});
```

Add to the test file's imports: `use Sylament\Support\Enums\SlideOverPosition;`

- [ ] **Step 2: Run to verify failure**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter="chrome toggles|sticky header and footer"`
Expected: FAIL — `Call to undefined method … isModalSlideOver()`.

- [ ] **Step 3: Implement** — in `CanOpenModal.php` add imports `use Sylament\Support\Enums\SlideOverPosition;` and properties (alongside the existing ones, alphabetical-ish with the current layout):

```php
    protected bool|Closure|null $hasModalCloseButton = null;

    protected bool|Closure|null $isModalAutofocused = null;

    protected bool|Closure|null $isModalClickThrough = null;

    protected bool|Closure|null $isModalClosedByClickingAway = null;

    protected bool|Closure|null $isModalClosedByEscaping = null;

    protected bool|Closure|null $isModalFooterSticky = null;

    protected bool|Closure|null $isModalHeaderSticky = null;

    protected bool|Closure $isModalSlideOver = false;

    protected SlideOverPosition|Closure|null $modalSlideOverPosition = null;
```

Setters (Filament-exact names and signatures; place with the other setters):

```php
    public function slideOver(bool|Closure $condition = true): static
    {
        $this->isModalSlideOver = $condition;

        return $this;
    }

    public function slideOverPosition(SlideOverPosition|Closure|null $position = null): static
    {
        $this->modalSlideOverPosition = $position;

        return $this;
    }

    public function stickyModalHeader(bool|Closure $condition = true): static
    {
        $this->isModalHeaderSticky = $condition;

        return $this;
    }

    public function stickyModalFooter(bool|Closure $condition = true): static
    {
        $this->isModalFooterSticky = $condition;

        return $this;
    }

    public function modalCloseButton(bool|Closure|null $condition = true): static
    {
        $this->hasModalCloseButton = $condition;

        return $this;
    }

    public function closeModalByClickingAway(bool|Closure|null $condition = true): static
    {
        $this->isModalClosedByClickingAway = $condition;

        return $this;
    }

    public function closeModalByEscaping(bool|Closure|null $condition = true): static
    {
        $this->isModalClosedByEscaping = $condition;

        return $this;
    }

    public function modalAutofocus(bool|Closure|null $condition = true): static
    {
        $this->isModalAutofocused = $condition;

        return $this;
    }

    public function modalClickThrough(bool|Closure|null $condition = true): static
    {
        $this->isModalClickThrough = $condition;

        return $this;
    }
```

Getters (the `?? true` literals are the deferred `ModalComponent` static seam — Filament reads `ModalComponent::$hasCloseButton` etc. here):

```php
    public function isModalSlideOver(): bool
    {
        return (bool) $this->evaluate($this->isModalSlideOver);
    }

    public function getModalSlideOverPosition(): ?SlideOverPosition
    {
        return $this->evaluate($this->modalSlideOverPosition);
    }

    public function isModalHeaderSticky(): bool
    {
        return (bool) ($this->evaluate($this->isModalHeaderSticky) ?? $this->isModalSlideOver());
    }

    public function isModalFooterSticky(): bool
    {
        return (bool) ($this->evaluate($this->isModalFooterSticky) ?? $this->isModalSlideOver());
    }

    public function hasModalCloseButton(): bool
    {
        return $this->evaluate($this->hasModalCloseButton) ?? true;
    }

    public function isModalClosedByClickingAway(): bool
    {
        return (bool) ($this->evaluate($this->isModalClosedByClickingAway) ?? true);
    }

    public function isModalClosedByEscaping(): bool
    {
        return (bool) ($this->evaluate($this->isModalClosedByEscaping) ?? true);
    }

    public function isModalAutofocused(): bool
    {
        return $this->evaluate($this->isModalAutofocused) ?? true;
    }

    public function isModalClickThrough(): bool
    {
        return (bool) $this->evaluate($this->isModalClickThrough);
    }
```

- [ ] **Step 4: Run to verify pass**

Run: `composer test:actions && composer test:static`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
composer format
git add packages/actions/src/Concerns/CanOpenModal.php packages/actions/tests/Unit/Concerns/CanOpenModalTest.php
git commit -m "feat(actions): CanOpenModal chrome toggles — slide-over, sticky panes, close/escape/autofocus/click-through"
```

---

### Task 4: Modal alignment defaults + footer center-reverse (actions)

**Files:**
- Modify: `packages/actions/src/Concerns/CanOpenModal.php`
- Test: `packages/actions/tests/Unit/Concerns/CanOpenModalTest.php`

**Interfaces:**
- Consumes: `isConfirmationRequired()` (existing), `getModalWidth()` (existing, returns `string`, default `'md'` — deliberately unchanged).
- Produces (consumed by Tasks 6, 8): `modalAlignment(Alignment|string|Closure|null $alignment = null): static`, `getModalAlignment(): Alignment|string`; `modalFooterActionsAlignment(Alignment|string|Closure|null $alignment = null): static`, `getModalFooterActionsAlignment(): Alignment|string|null`; `getModalFooterActions()` now reverses on center alignment (composed branch only).

- [ ] **Step 1: Write the failing tests** — append to `CanOpenModalTest.php` (import `use Sylament\Support\Enums\Alignment;`):

```php
it('defaults the modal alignment from confirmation and width', function (): void {
    expect(Action::make('view')->getModalAlignment())->toBe(Alignment::Start)
        ->and(Action::make('view')->modalWidth('xs')->getModalAlignment())->toBe(Alignment::Center)
        ->and(Action::make('view')->modalWidth('sm')->getModalAlignment())->toBe(Alignment::Center)
        ->and(Action::make('delete')->requiresConfirmation()->getModalAlignment())->toBe(Alignment::Center)
        ->and(Action::make('view')->modalAlignment(Alignment::End)->getModalAlignment())->toBe(Alignment::End)
        ->and(Action::make('view')->modalAlignment('custom')->getModalAlignment())->toBe('custom');
});

it('defaults the footer actions alignment to center only for confirmation', function (): void {
    expect(Action::make('view')->getModalFooterActionsAlignment())->toBeNull()
        ->and(Action::make('delete')->requiresConfirmation()->getModalFooterActionsAlignment())->toBe(Alignment::Center)
        ->and(Action::make('view')->modalFooterActionsAlignment('end')->getModalFooterActionsAlignment())->toBe('end');
});

it('reverses composed footer actions for center alignment', function (): void {
    $centered = Action::make('delete')
        ->extraModalFooterActions([Action::make('extra')])
        ->modalFooterActionsAlignment(Alignment::Center);

    expect(array_keys($centered->getModalFooterActions()))->toBe(['cancel', 'extra', 'submit']);

    $stringCentered = Action::make('delete')->modalFooterActionsAlignment('center');

    expect(array_keys($stringCentered->getModalFooterActions()))->toBe(['cancel', 'submit']);

    $overridden = Action::make('delete')
        ->modalFooterActions([Action::make('one'), Action::make('two')])
        ->modalFooterActionsAlignment(Alignment::Center);

    expect(array_keys($overridden->getModalFooterActions()))->toBe(['one', 'two']);
});
```

- [ ] **Step 2: Run to verify failure**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter="modal alignment|footer actions alignment|reverses composed"`
Expected: FAIL — `Call to undefined method … getModalAlignment()`.

- [ ] **Step 3: Implement** — add import `use Sylament\Support\Enums\Alignment;`, properties:

```php
    protected Alignment|string|Closure|null $modalAlignment = null;

    protected Alignment|string|Closure|null $modalFooterActionsAlignment = null;
```

Setters + getters (Filament-exact; width check adapted to our string widths — documented divergence-free since outcomes match):

```php
    public function modalAlignment(Alignment|string|Closure|null $alignment = null): static
    {
        $this->modalAlignment = $alignment;

        return $this;
    }

    public function modalFooterActionsAlignment(Alignment|string|Closure|null $alignment = null): static
    {
        $this->modalFooterActionsAlignment = $alignment;

        return $this;
    }

    public function getModalAlignment(): Alignment|string
    {
        if ($alignment = $this->evaluate($this->modalAlignment)) {
            return $alignment;
        }

        if ($this->isConfirmationRequired() || in_array($this->getModalWidth(), ['xs', 'sm'], true)) {
            return Alignment::Center;
        }

        return Alignment::Start;
    }

    public function getModalFooterActionsAlignment(): Alignment|string|null
    {
        if ($alignment = $this->evaluate($this->modalFooterActionsAlignment)) {
            return $alignment;
        }

        if ($this->isConfirmationRequired()) {
            return Alignment::Center;
        }

        return null;
    }
```

In `getModalFooterActions()`, insert immediately before the **final** `return $this->cachedModalFooterActions = $actions;` (the composed branch — NOT the `$this->modalFooterActions !== null` early-return branch):

```php
        if (in_array($this->getModalFooterActionsAlignment(), [Alignment::Center, 'center'], true)) {
            $actions = array_reverse($actions);
        }
```

- [ ] **Step 4: Run to verify pass**

Run: `composer test:actions && composer test:static`
Expected: PASS (the existing footer-action ordering tests still pass — no confirmation/center alignment in them).

- [ ] **Step 5: Format and commit**

```bash
composer format
git add packages/actions/src/Concerns/CanOpenModal.php packages/actions/tests/Unit/Concerns/CanOpenModalTest.php
git commit -m "feat(actions): modal alignment + footer-actions alignment with center-reversed composed footer"
```

---

### Task 5: Extra modal window/overlay attribute traits (actions)

**Files:**
- Create: `packages/actions/src/Concerns/HasExtraModalWindowAttributes.php`
- Create: `packages/actions/src/Concerns/HasExtraModalOverlayAttributes.php`
- Modify: `packages/actions/src/Action.php` (two `use` lines + imports)
- Test: `packages/actions/tests/Unit/Concerns/HasExtraModalWindowAttributesTest.php`
- Test: `packages/actions/tests/Unit/Concerns/HasExtraModalOverlayAttributesTest.php`

**Interfaces:**
- Consumes: `Sylament\Support\Twig\ComponentAttributeBag` (existing; `merge()` keeps existing scalar values, appends `class`/`style` token lists) — mirror of support's `HasExtraAttributes`.
- Produces (consumed by Task 8): `extraModalWindowAttributes(array|Closure $attributes, bool $merge = false): static`, `getExtraModalWindowAttributes(): array`, `getExtraModalWindowAttributeBag(): ComponentAttributeBag`; same trio with `Overlay`.

- [ ] **Step 1: Write the failing tests.** `HasExtraModalWindowAttributesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Support\Twig\ComponentAttributeBag;

it('defaults to no extra modal window attributes', function (): void {
    expect(Action::make('delete')->getExtraModalWindowAttributes())->toBe([]);
});

it('replaces by default and merges on request', function (): void {
    $action = Action::make('delete')->extraModalWindowAttributes(['data-a' => '1', 'class' => 'one']);

    expect($action->extraModalWindowAttributes(['data-b' => '2'])->getExtraModalWindowAttributes())
        ->toBe(['data-b' => '2']);

    $merged = Action::make('delete')
        ->extraModalWindowAttributes(['data-a' => '1', 'class' => 'one'])
        ->extraModalWindowAttributes(fn (): array => ['data-b' => '2', 'class' => 'two'], merge: true)
        ->getExtraModalWindowAttributes();

    expect($merged['data-a'])->toBe('1')
        ->and($merged['data-b'])->toBe('2')
        ->and($merged['class'])->toBe(['one', 'two']);
});

it('exposes an attribute bag', function (): void {
    expect(Action::make('delete')->extraModalWindowAttributes(['data-a' => '1'])->getExtraModalWindowAttributeBag())
        ->toBeInstanceOf(ComponentAttributeBag::class);
});
```

`HasExtraModalOverlayAttributesTest.php` — identical shape with `Overlay` substituted for `Window` in every method name.

Note: the `class` merge expectation (`['one', 'two']`) matches `ComponentAttributeBag::merge()`'s token-list append; if the bag normalizes differently, assert what `HasExtraAttributes`' behavior actually produces — the contract is "mirror `HasExtraAttributes` exactly".

- [ ] **Step 2: Run to verify failure**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter="ExtraModal"`
Expected: FAIL — undefined method `extraModalWindowAttributes()`.

- [ ] **Step 3: Implement.** `HasExtraModalWindowAttributes.php` (a namespace-adjusted mirror of `packages/support/src/Components/Concerns/HasExtraAttributes.php`):

```php
<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

trait HasExtraModalWindowAttributes
{
    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraModalWindowAttributes = [];

    /**
     * @param array<mixed>|Closure $attributes
     */
    public function extraModalWindowAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraModalWindowAttributes[] = $attributes;
        } else {
            $this->extraModalWindowAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraModalWindowAttributes(): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraModalWindowAttributes as $extraModalWindowAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate($extraModalWindowAttributes) ?: []);
        }

        return $attributeBag->getAttributes();
    }

    public function getExtraModalWindowAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraModalWindowAttributes());
    }
}
```

`HasExtraModalOverlayAttributes.php` — the same file with `Window` → `Overlay` in the trait name, property, and all three methods.

In `Action.php`: add `use Sylament\Actions\Concerns\HasExtraModalOverlayAttributes;` and `use Sylament\Actions\Concerns\HasExtraModalWindowAttributes;` imports and `use HasExtraModalOverlayAttributes;` / `use HasExtraModalWindowAttributes;` in the class body (alphabetical position within the existing `use` block).

- [ ] **Step 4: Run to verify pass**

Run: `composer test:actions && composer test:static`
Expected: PASS.

- [ ] **Step 5: Format and commit**

```bash
composer format
git add packages/actions/src/Concerns/HasExtraModal*Attributes.php packages/actions/src/Action.php packages/actions/tests/Unit/Concerns/HasExtraModal*AttributesTest.php
git commit -m "feat(actions): HasExtraModalWindowAttributes + HasExtraModalOverlayAttributes on Action"
```

---

### Task 6: Support Modal template restructure + chrome props (support + actions tests)

**Files:**
- Modify: `packages/support/templates/components/Modal/index.html.twig` (full rewrite below)
- Test: `packages/actions/tests/Integration/Components/ModalRenderTest.php`

**Interfaces:**
- Consumes: Task 1's enum/alias, Task 2's controller actions/values, support `IconButton` component, `attrs()` (later sources win, class/style append), `classes()`, `generateIconHtml()`, `stimulus_controller`/`stimulus_action`.
- Produces (consumed by Tasks 7, 8): the container/window DOM — `dialog.sy.sy-modal` root carrying variant classes `sy-modal-slide-over`, `sy-modal-slide-over-from-start|-from-end`, `sy-modal-has-sticky-header`, `sy-modal-has-sticky-footer`, `sy-modal-click-through`, `sy-modal-screen`, `sy-clickable`, plus `div.sy-modal-window` carrying `sy-modal-<width>`, `sy-align-<value>`, `sy-modal-window-has-close-btn|has-icon|has-content|has-footer`; props listed in the template header below.

- [ ] **Step 1: Update + extend the render tests.** In `ModalRenderTest.php`, first update the two width assertions for the moved class (in `renders a closed dialog…` change `->toContain('class="sy sy-modal sy-modal-md"')` to `->toContain('class="sy sy-modal"')` and add `->toContain('sy-modal-window sy-modal-md')`; in `applies a semantic width` change the expectation to `->toContain('sy-modal-window sy-modal-lg')`), give the helper a context parameter:

```php
function renderModal(string $template, array $context = []): string
{
    return test()->getContainer()->get('twig')->createTemplate($template)->render($context);
}
```

then append:

```php
it('renders the close button by default inside the header', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" heading="H" />');

    expect($html)->toContain('sy-modal-close-btn')
        ->toContain('aria-label="Close"')
        ->toContain('tabindex="-1"')
        ->toContain('sylament--support--modal#dismiss')
        ->toContain('sy-modal-window-has-close-btn');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :closeButton="false" />'))
        ->not->toContain('sy-modal-close-btn');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" />'))
        ->not->toContain('sy-modal-close-btn');
});

it('moves initial focus to the window when the close button is the only keyboard dismissal', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :closeByEscaping="false" />');

    expect($html)->toMatch('/sy-modal-window[^>]*autofocus/')
        ->toContain('data-sylament--support--modal-close-by-escaping-value="false"');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :closeByEscaping="false" />'))
        ->not->toMatch('/sy-modal-close-btn[^>]*tabindex="-1"/');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" :autofocus="false" />'))
        ->toMatch('/sy-modal-window[^>]*autofocus/');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" />'))
        ->not->toMatch('/sy-modal-window[^>]*autofocus/');
});

it('wires the chrome interaction actions on the dialog', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" />');

    expect($html)->toContain('cancel-&gt;sylament--support--modal#handleCancel')
        ->toContain('pointerdown-&gt;sylament--support--modal#trackPointerDown')
        ->toContain('click-&gt;sylament--support--modal#closeOnClickAway')
        ->toContain('close-&gt;sylament--support--modal#notifyDismissed')
        ->toContain('sy-clickable');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :closeByClickingAway="false" />'))
        ->toContain('data-sylament--support--modal-close-by-clicking-away-value="false"')
        ->not->toContain('sy-clickable');
});

it('renders slide-over and sticky variant classes on the root', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" :slideOver="true" />'))
        ->toContain('sy-modal-slide-over')
        ->toContain('sy-modal-slide-over-from-end');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :slideOver="true" slideOverPosition="start" />'))
        ->toContain('sy-modal-slide-over-from-start');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" :stickyHeader="true" :stickyFooter="true" />'))
        ->toContain('sy-modal-has-sticky-header')
        ->toContain('sy-modal-has-sticky-footer');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" width="screen" />'))
        ->toContain('sy-modal-screen');
});

it('marks click-through modals and disables click-away for them', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" :clickThrough="true" />');

    expect($html)->toContain('sy-modal-click-through')
        ->toContain('data-sylament--support--modal-click-through-value="true"')
        ->toContain('data-sylament--support--modal-close-by-clicking-away-value="false"');
});

it('applies alignment classes to the window and header', function (): void {
    expect(renderModal('<twig:SylamentSupport:Modal id="m" />'))
        ->toContain('sy-align-start');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" alignment="center" />'))
        ->toContain('sy-align-center');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" alignment="funky" />'))
        ->toMatch('/sy-modal-window[^"]*funky/');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" icon="heroicons:x-mark" />'))
        ->toContain('sy-vertical-align-center');

    expect(renderModal('<twig:SylamentSupport:Modal id="m" heading="H" description="D" icon="heroicons:x-mark" />'))
        ->not->toContain('sy-vertical-align-center');
});

it('renders footer actions in the wrapper unless a footer slot wins', function (): void {
    $actions = [
        \Sylament\Actions\Action::make('confirm')->button(),
        \Sylament\Actions\Action::make('back')->button(),
    ];

    $html = renderModal('<twig:SylamentSupport:Modal id="m" :footerActions="actions" footerActionsAlignment="center" />', ['actions' => $actions]);

    expect($html)->toContain('sy-modal-footer-actions')
        ->toContain('sy-align-center')
        ->toContain('Confirm')
        ->toContain('Back');

    $slotWins = renderModal('<twig:SylamentSupport:Modal id="m" :footerActions="actions"><twig:block name="footer"><em>slot</em></twig:block></twig:SylamentSupport:Modal>', ['actions' => $actions]);

    expect($slotWins)->toContain('<em>slot</em>')
        ->not->toContain('sy-modal-footer-actions');
});

it('spreads extra overlay and window attributes onto their elements', function (): void {
    $html = renderModal('<twig:SylamentSupport:Modal id="m" :extraOverlayAttributes="{\'data-overlay\': \'o\', class: \'overlay-extra\'}" :extraWindowAttributes="{\'data-window\': \'w\', class: \'window-extra\'}" />');

    expect($html)->toMatch('/<dialog[^>]*data-overlay="o"/')
        ->toMatch('/<dialog[^>]*class="[^"]*overlay-extra/')
        ->toMatch('/sy-modal-window[^>]*data-window="w"/')
        ->toMatch('/class="[^"]*sy-modal-window[^"]*window-extra/');
});
```

- [ ] **Step 2: Run to verify failure**

Run: `composer test:actions`
Expected: the new tests FAIL (missing props/classes); pre-existing tests still pass.

- [ ] **Step 3: Rewrite the template.** Replace `Modal/index.html.twig` with:

```twig
{% props
    alignment = 'start',
    autofocus = true,
    clickThrough = false,
    closeButton = true,
    closeByClickingAway = true,
    closeByEscaping = true,
    content = markup(block('content') ?? null),
    description = null,
    extraOverlayAttributes = {},
    extraWindowAttributes = {},
    footer = markup(block('footer') ?? null),
    footerActions = [],
    footerActionsAlignment = null,
    heading = null,
    icon = null,
    iconAlias = null,
    iconColor = 'primary',
    id = null,
    open = false,
    returnsFocus = true,
    slideOver = false,
    slideOverPosition = 'end',
    stickyHeader = false,
    stickyFooter = false,
    width = 'md' %}

{% set iconColorName = iconColor is iterable or iconColor is empty ? 'gray' : iconColor %}
{% set iconHtml = generateIconHtml(icon, iconAlias, {
    class: iconColorName != 'gray' ? ['sy-color', 'sy-color-' ~ iconColorName] : [],
}, 'lg') %}

{% set hasIcon = iconHtml is not empty %}
{% set hasHeading = heading is filled %}
{% set hasDescription = description is filled %}
{% set hasHeader = hasIcon or hasHeading or hasDescription %}
{% set hasCloseButton = closeButton and hasHeader %}
{% set hasFooterSlot = footer is filled %}
{% set hasFooterActions = footerActions is not empty %}
{% set hasFooter = hasFooterSlot or hasFooterActions %}
{% set hasContent = content is filled %}

{% set clickThrough = clickThrough ?: false %}
{% set closeByClickingAway = clickThrough ? false : closeByClickingAway %}
{% set sizeValue = width.value ?? width %}
{% set slideOverPositionValue = slideOverPosition.value ?? slideOverPosition ?? 'end' %}
{% set knownAlignments = ['start', 'left', 'center', 'end', 'right'] %}
{% set alignmentValue = alignment.value ?? alignment %}
{% set alignmentClass = alignmentValue is empty
    ? null
    : (alignmentValue in knownAlignments ? 'sy-align-' ~ alignmentValue : alignmentValue) %}
{% set footerAlignmentValue = footerActionsAlignment.value ?? footerActionsAlignment %}
{% set footerAlignmentClass = footerAlignmentValue is empty
    ? null
    : (footerAlignmentValue in knownAlignments ? 'sy-align-' ~ footerAlignmentValue : footerAlignmentValue) %}
{% set verticalAlignCenter = hasIcon and hasHeading and not hasDescription and alignmentValue in ['start', 'left'] %}
{% set windowGetsInitialFocus = (hasCloseButton and not closeByEscaping and hasHeading) or not autofocus %}

{% set headingId = id ? id ~ '-heading' : null %}
{% set descriptionId = (hasDescription and hasHeading and id) ? id ~ '-description' : null %}

{% set modalController = 'sylament--support--modal' %}
{% set modalStimulus = stimulus_controller(modalController, {
    id: id ?: null,
    open: open ?: null,
    returnsFocus: returnsFocus ? null : false,
    closeByEscaping: closeByEscaping ? null : false,
    closeByClickingAway: closeByClickingAway ? null : false,
    clickThrough: clickThrough ?: null,
})
    |stimulus_action(modalController, 'open', 'sylament-open-modal@window')
    |stimulus_action(modalController, 'close', 'sylament-close-modal@window')
    |stimulus_action(modalController, 'handleCancel', 'cancel')
    |stimulus_action(modalController, 'trackPointerDown', 'pointerdown')
    |stimulus_action(modalController, 'closeOnClickAway', 'click')
    |stimulus_action(modalController, 'notifyDismissed', 'close')
    |stimulus_action(modalController, 'restoreFocus', 'close') %}

{% set overlayExtra = extraOverlayAttributes ?: {} %}

<dialog
    {{ attributes.defaults({
        class: classes('sy sy-modal', {
            'sy-modal-slide-over': slideOver,
            'sy-modal-slide-over-from-start': slideOver and slideOverPositionValue == 'start',
            'sy-modal-slide-over-from-end': slideOver and slideOverPositionValue == 'end',
            'sy-modal-has-sticky-header': stickyHeader,
            'sy-modal-has-sticky-footer': stickyFooter,
            'sy-modal-click-through': clickThrough,
            'sy-modal-screen': sizeValue == 'screen',
            'sy-clickable': closeByClickingAway,
        }, overlayExtra.class ?? null),
        id: id ?: false,
        'aria-labelledby': hasHeading and headingId ? headingId : false,
        'aria-describedby': descriptionId ?: false,
        ...modalStimulus,
        ...(overlayExtra|filter((value, name) => name != 'class')),
    }) }}
>
    <div {{ attrs({
        class: classes('sy-modal-window', {
            ('sy-modal-' ~ sizeValue): sizeValue is not empty,
            (alignmentClass ?? ''): alignmentClass is not empty,
            'sy-modal-window-has-close-btn': hasCloseButton,
            'sy-modal-window-has-icon': hasIcon,
            'sy-modal-window-has-content': hasContent,
            'sy-modal-window-has-footer': hasFooter,
        }),
        autofocus: windowGetsInitialFocus,
        tabindex: windowGetsInitialFocus ? '-1' : false,
    }, extraWindowAttributes ?: {}) }}>
        {% if hasHeader %}
            <header {{ attrs({ class: classes('sy-modal-header', { 'sy-vertical-align-center': verticalAlignCenter }) }) }}>
                {% if hasCloseButton %}
                    <twig:SylamentSupport:IconButton
                        class="sy-modal-close-btn"
                        color="gray"
                        icon="heroicons:x-mark"
                        :iconAlias="constant('Sylament\\Support\\Twig\\SupportIconAlias::MODAL_CLOSE_BUTTON')"
                        iconSize="lg"
                        label="Close"
                        :tabindex="closeByEscaping ? '-1' : false"
                        data-action="sylament--support--modal#dismiss"
                    />
                {% endif %}

                {% if hasIcon %}
                    <div class="sy-modal-icon-ctn">{{ iconHtml }}</div>
                {% endif %}

                {% if hasHeading or hasDescription %}
                    <div class="sy-modal-header-text-ctn">
                        {% if hasHeading %}
                            <h2 {{ attrs({ id: headingId, class: ['sy-modal-heading'] }) }}>{{ heading }}</h2>
                        {% endif %}

                        {% if hasDescription %}
                            <p {{ attrs({ id: descriptionId, class: ['sy-modal-description'] }) }}>{{ description }}</p>
                        {% endif %}
                    </div>
                {% endif %}
            </header>
        {% endif %}

        {% if hasContent %}
            <div class="sy-modal-content">{{ content }}</div>
        {% endif %}

        {% if hasFooter %}
            <footer {{ attrs({ class: classes('sy-modal-footer', { (footerAlignmentClass ?? ''): footerAlignmentClass is not empty }) }) }}>
                {% if hasFooterSlot %}
                    {{ footer }}
                {% else %}
                    <div class="sy-modal-footer-actions">
                        {% for footerAction in footerActions %}
                            {{- renderComponent(footerAction) -}}
                        {% endfor %}
                    </div>
                {% endif %}
            </footer>
        {% endif %}
    </div>
</dialog>
```

Implementation notes for this step:
- If `:tabindex="… ? '-1' : false"` on the IconButton renders a literal `tabindex="false"` (ux-twig-component may not drop `false` attribute values the way `attrs()` does), switch to two `<twig:SylamentSupport:IconButton …>` branches in an `{% if closeByEscaping %}` — the render test's `not->toMatch('/sy-modal-close-btn[^>]*tabindex="-1"/')` catches this.
- If the `constant()` call for the icon alias is awkward, an equivalent accepted form is `iconAlias="modal.close-button"` with a comment pointing at `SupportIconAlias::MODAL_CLOSE_BUTTON` — prefer the constant if it renders clean.
- The `sy-clickable` class is cursor styling only; the click listener is always wired and gated by the controller value.

- [ ] **Step 4: Run to verify pass**

Run: `composer test:actions`
Expected: PASS — including the pre-existing Modal/ActionModal/Modals render tests (`ActionModalRenderTest` sees new default chrome — close button markup, `sy-align-*` classes — but its assertions are `toContain`-style and survive; if any fails, fix forward here, not by weakening the new behavior).

- [ ] **Step 5: Commit**

```bash
git add packages/support/templates/components/Modal/index.html.twig packages/actions/tests/Integration/Components/ModalRenderTest.php
git commit -m "feat(support): Modal container/window restructure with full chrome prop surface"
```

---

### Task 7: Modal CSS rewrite (support)

**Files:**
- Modify: `packages/support/assets/src/styles/components/modal.css` (full rewrite below)

**Interfaces:**
- Consumes: the class contract produced by Task 6 (root variants on `dialog.sy-modal`, window classes on `.sy-modal-window`).
- Produces: visual behavior only — no other task consumes selectors from here.

- [ ] **Step 1: Replace `modal.css`** with:

```css
.sy-modal {
    /* Container = overlay: fullscreen in the top layer, paints the dim, centers the window.
       Closed dialogs are hidden by the author rule (not just UA) so `display` can
       transition out via allow-discrete. */
    @apply fixed inset-0 m-0 hidden size-auto max-h-none max-w-none grid-rows-[1fr_auto_1fr] justify-items-center bg-transparent p-0 text-gray-950 outline-none transition-discrete duration-300 sm:grid-rows-[1fr_auto_3fr] dark:text-white;
    transition-property: display, overlay, background-color;

    &[open] {
        @apply grid bg-gray-950/50 dark:bg-gray-950/75;

        @starting-style {
            @apply bg-transparent;
        }
    }

    &::backdrop {
        @apply hidden;
    }

    &.sy-clickable {
        @apply cursor-pointer;
    }

    & > .sy-modal-window {
        @apply pointer-events-auto relative row-start-2 flex w-full scale-95 cursor-default flex-col bg-white opacity-0 shadow-xl ring-1 ring-gray-950/5 outline-none transition-[transform,opacity] duration-300 dark:bg-gray-900 dark:ring-white/10;

        &.sy-modal-3xs { @apply max-w-3xs; }
        &.sy-modal-2xs { @apply max-w-2xs; }
        &.sy-modal-xs { @apply max-w-xs; }
        &.sy-modal-sm { @apply max-w-sm; }
        &.sy-modal-md { @apply max-w-md; }
        &.sy-modal-lg { @apply max-w-lg; }
        &.sy-modal-xl { @apply max-w-xl; }
        &.sy-modal-2xl { @apply max-w-2xl; }
        &.sy-modal-3xl { @apply max-w-3xl; }
        &.sy-modal-4xl { @apply max-w-4xl; }
        &.sy-modal-5xl { @apply max-w-5xl; }
        &.sy-modal-6xl { @apply max-w-6xl; }
        &.sy-modal-7xl { @apply max-w-7xl; }
        &.sy-modal-screen { @apply fixed inset-0 max-w-none; }

        & .sy-modal-header {
            @apply flex px-6 pt-6;

            &.sy-vertical-align-center {
                @apply items-center;
            }
        }

        & .sy-modal-icon-ctn {
            @apply shrink-0;

            & > .sy-icon {
                @apply text-gray-500 dark:text-gray-400;

                &.sy-color {
                    @apply text-color-600 dark:text-color-400;
                }
            }
        }

        & .sy-modal-header-text-ctn {
            @apply flex-1;
        }

        & .sy-modal-heading {
            @apply text-base leading-6 font-semibold text-gray-950 dark:text-white;
        }

        & .sy-modal-description {
            @apply mt-2 text-sm text-gray-500 dark:text-gray-400;
        }

        & .sy-modal-content {
            @apply flex flex-col gap-y-4 py-6;
        }

        & .sy-modal-close-btn {
            @apply absolute;
        }

        &.sy-align-start,
        &.sy-align-left {
            & .sy-modal-header {
                @apply gap-x-5;
            }
        }

        &.sy-align-center {
            & .sy-modal-header {
                @apply flex-col text-center;
            }

            & .sy-modal-icon-ctn {
                @apply mb-5 flex items-center justify-center;
            }
        }

        &.sy-modal-window-has-close-btn {
            &.sy-align-center:not(.sy-modal-window-has-icon) {
                & .sy-modal-heading {
                    @apply ms-6;
                }
            }

            &:not(.sy-modal-window-has-icon),
            &.sy-align-start,
            &.sy-align-left {
                & .sy-modal-heading {
                    @apply me-6;
                }
            }
        }

        & .sy-modal-footer {
            @apply w-full pb-6;

            & .sy-modal-footer-actions {
                @apply gap-3;
            }

            &.sy-align-start,
            &.sy-align-left {
                & .sy-modal-footer-actions {
                    @apply flex flex-wrap items-center;
                }
            }

            &.sy-align-center {
                @apply px-6;

                & .sy-modal-footer-actions {
                    @apply flex flex-col-reverse;
                }
            }

            &.sy-align-end,
            &.sy-align-right {
                & .sy-modal-footer-actions {
                    @apply flex flex-row-reverse flex-wrap items-center;
                }
            }
        }
    }

    @supports (container-type: inline-size) {
        & > .sy-modal-window .sy-modal-footer.sy-align-center {
            @apply @container;

            & .sy-modal-footer-actions {
                @apply @sm:grid @sm:grid-cols-[repeat(auto-fit,minmax(0,1fr))];
            }
        }
    }

    @supports not (container-type: inline-size) {
        & > .sy-modal-window .sy-modal-footer.sy-align-center .sy-modal-footer-actions {
            @apply sm:grid sm:grid-cols-[repeat(auto-fit,minmax(0,1fr))];
        }
    }

    /* Open-state window transition targets (the closed state above is the start/end frame). */
    &[open] > .sy-modal-window {
        @apply scale-100 opacity-100;

        @starting-style {
            @apply scale-95 opacity-0;
        }
    }

    /* Centered (non-slide-over, non-screen) frame: container padding, rounded window,
       container scroll unless a sticky pane moves the scroll inside the window. */
    &:not(.sy-modal-slide-over):not(.sy-modal-screen) {
        & > .sy-modal-window {
            @apply mx-auto rounded-xl;

            & .sy-modal-close-btn {
                @apply end-4 top-4;
            }
        }

        &[open] {
            @apply p-4;
        }

        &:not(.sy-modal-has-sticky-header):not(.sy-modal-has-sticky-footer)[open] {
            @apply overflow-y-auto;
        }

        &.sy-modal-has-sticky-header,
        &.sy-modal-has-sticky-footer {
            & > .sy-modal-window {
                @apply max-h-[calc(100dvh-2rem)] overflow-y-auto;
            }
        }

        &.sy-modal-has-sticky-header > .sy-modal-window > .sy-modal-header {
            @apply rounded-t-xl;
        }

        &.sy-modal-has-sticky-footer > .sy-modal-window > .sy-modal-footer {
            @apply rounded-b-xl;
        }
    }

    &.sy-modal-has-sticky-header {
        & > .sy-modal-window > .sy-modal-header {
            @apply sticky top-0 z-10 border-b border-gray-200 bg-white pb-6 dark:border-white/10 dark:bg-gray-900;
        }

        & > .sy-modal-window {
            & .sy-modal-content,
            & .sy-modal-footer {
                @apply px-6;
            }
        }
    }

    &.sy-modal-has-sticky-footer > .sy-modal-window > .sy-modal-footer {
        @apply sticky bottom-0 border-t border-gray-200 bg-white py-5 dark:border-white/10 dark:bg-gray-900;
    }

    &:not(.sy-modal-has-sticky-footer) > .sy-modal-window:not(.sy-modal-window-has-content) {
        & .sy-modal-footer {
            @apply mt-6;
        }

        &:not(.sy-modal-window-has-footer) .sy-modal-header {
            @apply pb-6;
        }
    }

    /* Start-aligned content indent under the heading when an icon is present:
       px-6 (1.5rem) + lg icon (1.5rem) + gap-x-5 (1.25rem) = 4.25rem. */
    &:not(.sy-modal-has-sticky-header) {
        & > .sy-modal-window.sy-modal-window-has-icon {
            &.sy-align-start,
            &.sy-align-left {
                & .sy-modal-content {
                    @apply ps-[4.25rem] pe-6;
                }

                & .sy-modal-footer:not(.sy-align-center) {
                    @apply ps-[4.25rem] pe-6;
                }
            }
        }

        & > .sy-modal-window:not(.sy-modal-window-has-icon),
        & > .sy-modal-window.sy-align-center {
            & .sy-modal-content,
            & .sy-modal-footer {
                @apply px-6;
            }
        }
    }

    &.sy-modal-has-sticky-header > .sy-modal-window {
        & .sy-modal-content,
        & .sy-modal-footer {
            @apply px-6;
        }
    }

    /* Slide-over: full-height edge panel; sticky panes are the norm (PHP defaults them on). */
    &.sy-modal-slide-over,
    &.sy-modal-screen {
        & > .sy-modal-window {
            @apply h-dvh max-h-none;

            & .sy-modal-content {
                @apply flex-1;
            }
        }
    }

    &.sy-modal-slide-over {
        & > .sy-modal-window {
            @apply scale-100 overflow-y-auto opacity-100 transition-transform;

            & .sy-modal-header {
                @apply gap-x-3 pt-5;
            }

            & .sy-modal-close-btn {
                @apply end-6 top-5;
            }

            & .sy-modal-footer {
                @apply mt-auto;
            }
        }

        &.sy-modal-slide-over-from-start > .sy-modal-window {
            @apply me-auto -translate-x-full rtl:translate-x-full;
        }

        &.sy-modal-slide-over-from-end > .sy-modal-window {
            @apply ms-auto translate-x-full rtl:-translate-x-full;
        }

        &[open] > .sy-modal-window {
            @apply translate-x-0;

            @starting-style {
                @apply rtl:translate-x-full;
            }
        }

        &[open].sy-modal-slide-over-from-start > .sy-modal-window {
            @starting-style {
                @apply -translate-x-full rtl:translate-x-full;
            }
        }

        &[open].sy-modal-slide-over-from-end > .sy-modal-window {
            @starting-style {
                @apply translate-x-full rtl:-translate-x-full;
            }
        }

        &.sy-modal-has-sticky-header > .sy-modal-window > .sy-modal-header {
            @apply pb-5;
        }
    }

    /* Screen-width: no movement animation (Filament suppresses the transition frames). */
    &.sy-modal-screen > .sy-modal-window {
        @apply scale-100 opacity-100 transition-none;
    }

    /* Click-through: a non-modal panel — dialog.show() leaves the top layer, so position
       and stacking are explicit; the container ignores pointer events, the window takes
       them back (Filament's own mechanism). No dim in this mode. */
    &.sy-modal-click-through {
        @apply pointer-events-none z-40;

        &[open] {
            @apply bg-transparent;
        }
    }
}
```

- [ ] **Step 2: Build to verify**

Run: `pnpm build && pnpm test:lint`
Expected: build green (do NOT commit `dist/` here — Task 9 owns the single dist rebuild). If `max-w-3xs`/`max-w-2xs` are unknown to the installed Tailwind, replace those two lines with `max-width: 16rem;` / `max-width: 18rem;` plain CSS and note it.

- [ ] **Step 3: Manual spot-check (optional but recommended)**

If a demo checkout is available, render a `slideOver()` action and a `requiresConfirmation()` action; verify slide-in, dim fade, sticky header borders, centered confirmation with cancel-first footer.

- [ ] **Step 4: Commit**

```bash
git checkout -- packages/support/assets/dist 2>/dev/null || true
git add packages/support/assets/src/styles/components/modal.css
git commit -m "feat(support): modal chrome CSS — container/window, slide-over, sticky, alignment, click-through, CSS-native transitions"
```

---

### Task 8: ActionModal pass-through + dismissed→unmount (actions)

**Files:**
- Modify: `packages/actions/templates/components/ActionModal/index.html.twig` (full rewrite below)
- Modify: `packages/actions/tests/Fixtures/Twig/Components/RecordActionHost.php` (add one fixture action)
- Test: `packages/actions/tests/Integration/Components/ActionModalRenderTest.php`

**Interfaces:**
- Consumes: every getter from Tasks 3–5 (exact names in those tasks' Produces blocks), Task 6's Modal props, UX LiveComponent's `live_action(actionName, parameters, modifiers, event)` Twig helper, the controller's `sylament--support--modal:dismissed` event (Task 2).
- Produces: the end-to-end action modal — no later task consumes new symbols.

- [ ] **Step 1: Add a slide-over fixture action.** In `RecordActionHost.php`, register alongside the existing actions (match the fixture's existing action-definition style):

```php
Action::make('sideSheet')
    ->modalHeading('Sheet')
    ->slideOver(),
```

- [ ] **Step 2: Write the failing tests** — append to `ActionModalRenderTest.php`:

```php
it('passes the chrome config through to the modal', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'sideSheet', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toContain('sy-modal-slide-over')
        ->toContain('sy-modal-slide-over-from-end')
        ->toContain('sy-modal-has-sticky-header')
        ->toContain('sy-modal-has-sticky-footer')
        ->toContain('sy-modal-close-btn');
});

it('centers and reverses the confirmation modal footer', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->render();

    $cancelPosition = mb_strpos($html, 'data-live-action-param="unmountAction"');
    $submitPosition = mb_strpos($html, 'data-live-action-param="callMountedAction"');

    expect($html)->toContain('sy-align-center')
        ->and($cancelPosition)->not->toBeFalse()
        ->and($submitPosition)->not->toBeFalse()
        ->and($cancelPosition)->toBeLessThan($submitPosition);
});

it('unmounts the action when the modal reports a client-side dismissal', function (): void {
    $html = (string) $this->createLiveComponent('RecordActionHost')
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['recordKey' => 1]])
        ->render();

    expect($html)->toContain('sylament--support--modal:dismissed-&gt;live#action');
});
```

Note: the confirmation reversal makes the *first* `unmountAction` occurrence the cancel button (before submit); the dismissal-wrapper's own `unmountAction` param sits on the wrapper div *before* the dialog, so `mb_strpos` still finds an occurrence before submit either way — the assertion is on relative order, which holds in both readings.

- [ ] **Step 3: Run to verify failure**

Run: `vendor/bin/pest -c packages/actions/phpunit.dist.xml --test-directory=packages/actions/tests --filter="chrome config|centers and reverses|client-side dismissal"`
Expected: FAIL (missing classes/bindings). Also expected now: the pre-existing `renders extra footer actions between submit and cancel` may pick up the wrapper's `unmountAction` occurrence — see Step 4 note.

- [ ] **Step 4: Rewrite `ActionModal/index.html.twig`:**

```twig
{% props
    action,
    id %}

{# The dismissal wrapper maps the controller's client-close event (ESC, click-away,
   close button) to a server unmount — without it the next live re-render would
   resurrect a modal the user already closed. The event bubbles from the dialog;
   the binding cannot sit on the component tag itself because a caller data-action
   would replace the dialog's own Stimulus wiring in attributes.defaults(). #}
<div {{ live_action('unmountAction', {}, {}, 'sylament--support--modal:dismissed') }}>
    {# Rendered only while the action is mounted and its modal should be open, so the open
       state is declared here rather than left to the `sylament-open-modal` event, which a
       modal first rendered by that same response is not yet listening for. #}
    <twig:SylamentSupport:Modal
        id="{{ id }}"
        :open="true"
        :heading="action.getModalHeading"
        :description="action.getModalDescription"
        :icon="action.getModalIcon"
        :iconColor="action.getModalIconColor"
        :width="action.getModalWidth"
        :alignment="action.getModalAlignment"
        :slideOver="action.isModalSlideOver"
        :slideOverPosition="action.getModalSlideOverPosition"
        :stickyHeader="action.isModalHeaderSticky"
        :stickyFooter="action.isModalFooterSticky"
        :closeButton="action.hasModalCloseButton"
        :closeByClickingAway="action.isModalClosedByClickingAway"
        :closeByEscaping="action.isModalClosedByEscaping"
        :autofocus="action.isModalAutofocused"
        :clickThrough="action.isModalClickThrough"
        :extraWindowAttributes="action.getExtraModalWindowAttributes"
        :extraOverlayAttributes="action.getExtraModalOverlayAttributes"
        :footerActions="action.getVisibleModalFooterActions"
        :footerActionsAlignment="action.getModalFooterActionsAlignment"
    >
        <twig:block name="content">
            {{- action.getModalContent -}}
            {{- action.getModalContentFooter -}}
        </twig:block>
    </twig:SylamentSupport:Modal>
</div>
```

Note: if the pre-existing `renders extra footer actions between submit and cancel` test fails because the wrapper's `data-live-action-param="unmountAction"` now precedes the submit button, anchor its cancel position search after the submit position (`mb_strpos($html, 'data-live-action-param="unmountAction"', $submitPosition)`) — the behavioral claim (cancel after extras) is unchanged.

- [ ] **Step 5: Run to verify pass**

Run: `composer test:actions && composer test:static`
Expected: PASS, including `ModalsRenderTest` (the wrapper div is inert for its assertions).

- [ ] **Step 6: Format and commit**

```bash
composer format
git add packages/actions/templates/components/ActionModal/index.html.twig packages/actions/tests/Fixtures/Twig/Components/RecordActionHost.php packages/actions/tests/Integration/Components/ActionModalRenderTest.php
git commit -m "feat(actions): full modal chrome pass-through + dismissed-to-unmount sync in ActionModal"
```

---

### Task 9: Docs trail, dist rebuild, full gates (integration close-out)

**Files:**
- Modify: `docs/progress/support/modal.md`
- Modify: `docs/progress/actions/action.md`
- Modify: `docs/progress/support/README.md`, `docs/progress/actions/README.md`, `docs/progress/README.md` (rollup rows only if their status text changes)
- Modify: `docs/progress/roadmap.md`
- Modify: `packages/support/assets/dist/**` (rebuilt, not hand-edited)

**Interfaces:** none produced; this task closes the epic.

- [ ] **Step 1: Update `docs/progress/support/modal.md`:**
  - Surface table: flip the "Close button + `ModalComponent` static config" row to `in progress` with a detail noting the close button is **done** and only the `ModalComponent` global-defaults seam remains (or split per the one-row rules if cleaner — the seam is the only remainder). Split the breadth row: slide-over, sticky header/footer, alignment, click-through, extra attribute bags are now `done`; `alert`, `trigger`, `teleport`, `visible`, `header` slot, `ariaLabelledby` stay `todo` in a slimmed row.
  - Rewrite the headline divergence "The modal is re-architected onto native `<dialog>`": the dialog is now the overlay+centering **container** painting the dim (`::backdrop` neutralized) with an inner `.sy-modal-window`; native top layer/focus-trap/ESC retained. Record the collapsed root/overlay attribute target (caller attrs + extra overlay attrs both land on the dialog).
  - Add divergence/translation entries (statement-style headings): ESC via the native `cancel` event (no `isTopmost()` port; repeated-ESC force-close platform caveat); click-away via the pointerdown+click origin pair (Filament's text-selection guard by construction); click-through via `dialog.show()` (non-modal semantics natively — no `aria-modal` stamping); transitions via `@starting-style`/`allow-discrete` replacing Alpine (close animation applies to client-side closes only); the `dismissed` event → the actions layer's unmount binding (Filament's `modal-closed` equivalent); default flips to parity (close button on, start-aligned footer — the end-reversed footer divergence is deleted); alignment strings emit `sy-align-*` for known values and pass through verbatim otherwise (Filament drops strings); `returnsFocus` stays true where Filament's action modal hardcodes false (Livewire artifact).
  - Update the footer divergence ("Footer actions, header slot, and alignment are not ported"): footer actions + alignment are now ported; only the `header` replacement slot remains.
- [ ] **Step 2: Update `docs/progress/actions/action.md`:**
  - `CanOpenModal` detail section: remove the slide-over/layout and interaction-chrome bullets from "Missing vs Filament" (they landed); keep wizard suppression + deprecated aliases; note the `?? true` literals as the deferred `ModalComponent` seam's insertion points. Move the row toward `done` only if every remaining listed gap is deliberate (`skipped`) or still tracked — otherwise stay `in progress` with the slimmed detail.
  - Modal-rendering row: clear the "CanOpenModal (slideover, sticky)" blocked-by; rewrite the "No modal-chrome configuration reaches the `Modal` component" bullet to reflect the full pass-through; document the dismissal wrapper.
  - Remove `HasExtraModalOverlayAttributes`/`HasExtraModalWindowAttributes` from the unported-surface divergence.
- [ ] **Step 3: Walk the rollups** (same-commit rule): `actions/README.md` + `support/README.md` rows for the files touched, then the top-level `docs/progress/README.md` package rows — only where status text actually changes.
- [ ] **Step 4: Flip the roadmap.** In `docs/progress/roadmap.md`: epic 4 row status → `done`; `## Now` → "**Pulled forward · 5 — nested actions + submenus.** The next epic to pick up."
- [ ] **Step 5: Rebuild dist and run every gate**

```bash
pnpm build
pnpm test:types
pnpm test:lint
composer format
composer test
```

Expected: all green; `git status` shows only intended files + rebuilt `packages/support/assets/dist`.

- [ ] **Step 6: Commit**

```bash
git add docs/progress packages/support/assets/dist
git commit -m "docs(progress): flip modal-chrome epic 4, advance Now cursor; rebuild support dist"
```

---

## Plan self-review (performed at write time)

- **Spec coverage:** every spec section maps to a task — PHP surface (T3/T4/T5), enum+alias (T1), template (T6), controller (T2), CSS+transitions (T7), pass-through+dismissed sync (T8), docs+gates (T9). Deferred list untouched by any task. ✓
- **Type consistency:** getter names in T8's template match T3/T4/T5's Produces blocks; controller action names in T6's template match T2's methods; class names in T7 match T6's emissions. ✓
- **Known judgment calls encoded:** dismissal binding on a wrapper div (data-action collision with `attributes.defaults()`); `tabindex` false-drop fallback branch in T6; Tailwind `max-w-3xs/2xs` fallback in T7; pre-existing footer-order test re-anchoring note in T8.
