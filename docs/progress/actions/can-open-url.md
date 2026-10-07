---
description: The trait that gives an action a URL, opened in the same or a new tab, with the host's default URL as fallback.
ports:
  - vendor/filament/actions/src/Concerns/CanOpenUrl.php
owns:
  - packages/actions/src/Concerns/CanOpenUrl.php
---
# CanOpenUrl (actions)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CanOpenUrl`](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | in progress | — | [detail](#geturl-carries-the-host-fallback-the-new-tab-introspection-helper-is-missing) |
| POST-to-url support | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | skipped | — | [diverges](#post-to-url-support-is-not-ported) |

## Details

### getUrl() carries the host fallback; the new-tab introspection helper is missing

Ported and test-covered: `url()`, `openUrlInNewTab()`, `getUrl()`'s closure evaluation, `hasUrl()`, `shouldOpenUrlInNewTab()`, and the modal-precedence guard — `getUrl()` returns `null` outright when `hasModal()` is true, with Filament's exact precision: the guard reads `hasModal()` (an explicit `->modal()` call), **not** `shouldOpenModal()`, so `->url(…)` + `->modalHeading(…)` still renders a link while `->url(…)` + `->modal()` suppresses the URL.
Exercised in [`CanOpenUrlTest`](../../../packages/actions/tests/Unit/Concerns/CanOpenUrlTest.php) against the [`CanOpenUrlFixture`](../../../packages/actions/tests/Fixtures/Concerns/CanOpenUrlFixture.php) (which stubs `hasModal()`, since the trait expects the composing class to supply it — `Action` does via `CanOpenModal`) plus `Action`-level precedence cases.

Also ported, and the reason the prebuilt [`CreateAction`](create-action.md) / [`EditAction`](edit-action.md) work at all: the host fallback.
When no explicit `url` closure resolves a value, `getUrl()` asks the host — `$this->evaluate($this->url) ?? $this->getLiveComponentOrNull()?->getDefaultActionUrl($this)`, Filament's line with its `getHasActionsLivewire()` replaced by the port's [null-tolerant accessor](action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter) (the throwing `getLiveComponent()` would turn "this action is not on a host" into an exception at render time, which is exactly the case that has to answer `null`).
The hook itself is [on the contract with a `null` default on the trait](interacts-with-actions.md#a-host-answering-the-url-hook-must-discriminate-by-action).
Precedence is Filament's: an explicit `url()` never consults the host, and the `hasModal()` guard still wins over both.

Missing vs Filament: `Action::render()` consults a plain introspection helper, `hasShouldOpenUrlInNewTab()`, when deciding button vs. link markup; Sylament has no equivalent (only the evaluated `shouldOpenUrlInNewTab()` exists).

Next step: add `hasShouldOpenUrlInNewTab()` with the optimized-render pipeline that consumes it.

## Divergences

### POST-to-url support is not ported

Filament's `CanOpenUrl` also carries `postToUrl()` / `shouldPostToUrl()` / `hasShouldPostToUrl()`, and `Action::render()` branches heavily on `shouldPostToUrl()` to emit a `<form method="post">` instead of an `<a>` (CSRF token, `action`/`method` attributes, disabled-state handling all differ from the plain-link path).
None of this exists in [`Sylament\Actions\Concerns\CanOpenUrl`](../../../packages/actions/src/Concerns/CanOpenUrl.php) — the omission is deliberate: no POST actions in the MVP.
`Action`'s current template (`../../../packages/actions/templates/partials/Action/Button.html.twig`) only ever emits `<a>` or `<button>`, never a `<form>`.
This is a scope decision for the current action surface, not a missing dependency, so it is **skipped** rather than **blocked** — reversible the moment an action needs a same-origin state-changing link (Filament's own use case: actions that must not be triggered by a GET, e.g. destructive links without JS).

The state-based `url` closure parameter Filament's **schemas** / **tables** `CanOpenUrl` siblings support never applied here: Filament's own **actions** `CanOpenUrl` — the source this row ports — never had a `$state` argument to begin with, so nothing was dropped on that front.
Support's [arity probe](../support/component.md#surface), which is what tells those siblings apart, is `EvaluatesClosures::evaluationValueIsFunctionAndHasParameter()` and reads the tables one; this row is unaffected either way.
