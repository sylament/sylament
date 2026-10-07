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
| [`url()` / `openUrlInNewTab()` / `getUrl()` / `hasUrl()` / `shouldOpenUrlInNewTab()`](../../../packages/actions/src/Concerns/CanOpenUrl.php) | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | done | — | [semantics](#an-explicit-modal-suppresses-the-url-modal-content-alone-does-not) · [semantics](#geturl-falls-back-to-the-hosts-default-url-through-the-nullable-accessor) |
| `hasShouldOpenUrlInNewTab()` | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | skipped | — | [diverges](#hasshouldopenurlinnewtab-is-not-ported-because-only-the-fast-path-renderers-read-it) |
| `postToUrl()` / `shouldPostToUrl()` / `hasShouldPostToUrl()` | [`Concerns/CanOpenUrl.php`](../../../vendor/filament/actions/src/Concerns/CanOpenUrl.php) | skipped | — | [diverges](#post-to-url-support-is-not-ported) |

## Semantics

### An explicit modal suppresses the URL; modal content alone does not

`getUrl()` returns `null` outright when `hasModal()` is true, with Filament's precision: the guard reads `hasModal()` (an explicit `->modal()` call), **not** `shouldOpenModal()`, so `->url(…)` with `->modalHeading(…)` still renders a link while `->url(…)` with `->modal()` suppresses the URL.
The trait expects the composing class to supply `hasModal()`; `Action` does through [`CanOpenModal`](can-open-modal.md).
Exercised in [`CanOpenUrlTest`](../../../packages/actions/tests/Unit/Concerns/CanOpenUrlTest.php) against the [`CanOpenUrlFixture`](../../../packages/actions/tests/Fixtures/Concerns/CanOpenUrlFixture.php), which stubs `hasModal()`.

### `getUrl()` falls back to the host's default URL through the nullable accessor

When no explicit `url` closure resolves a value, `getUrl()` asks the host: `$this->evaluate($this->url) ?? $this->getLiveComponentOrNull()?->getDefaultActionUrl($this)`, Filament's line with its `getHasActionsLivewire()` replaced by the port's [null-tolerant accessor](action.md#the-live-component-chain-narrows-inline-instead-of-through-a-second-getter).
The throwing `getLiveComponent()` would turn "this action is not on a host" into an exception at render time, which is the case that has to answer `null`.
The hook is [on the contract, with a `null` default on the trait](interacts-with-actions.md#a-host-answering-the-url-hook-must-discriminate-by-action), and it is what makes the prebuilt [`CreateAction`](create-action.md) and [`EditAction`](edit-action.md) open a page.
Precedence is Filament's: an explicit `url()` never consults the host, and the `hasModal()` guard wins over both.
Covered by `CanOpenUrlTest`'s host-hook cases and, rendered, by the Integration [`CanOpenUrlTest`](../../../packages/actions/tests/Integration/Concerns/CanOpenUrlTest.php).

## Divergences

### POST-to-url support is not ported

Filament's `CanOpenUrl` also carries `postToUrl()` / `shouldPostToUrl()` / `hasShouldPostToUrl()`, and its renderers branch on `shouldPostToUrl()` to emit a `<form method="post">` instead of an `<a>`, with a CSRF token, `action` and `method` attributes and disabled-state handling that all differ from the plain-link path.
None of this exists in [`Sylament\Actions\Concerns\CanOpenUrl`](../../../packages/actions/src/Concerns/CanOpenUrl.php), deliberately: no action posts.
The [trigger templates](../../../packages/actions/templates/partials/Action/) only emit an `<a>` or a `<button>`, never a `<form>`.
This is a scope decision for the action surface, not a missing dependency, so it is **skipped** rather than **blocked**, and it reverses the moment an action needs a same-origin state-changing link (Filament's own use case: actions that must not be triggered by a GET, such as destructive links without JS).

### `hasShouldOpenUrlInNewTab()` is not ported, because only the fast-path renderers read it

Filament reads it only in `canRenderOptimizedLink()` and `canRenderOptimizedGrouped()`, the guards of the string fast paths the port [does not have](action.md#the-view-switch-renders-through-twig-templates-not-tohtml-fast-paths).
