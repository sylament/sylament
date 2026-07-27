# build-js.mjs — structured JS build orchestration — design

Date: 2026-07-27
Status: approved (design); implementation plan to follow
Depends on: the ActionGroup epic (branch `feat/action-group-base`), which
introduced the bundled dropdown-controller entry this restructures.

## Why

`packages/support/assets/package.json` currently carries the per-file/bundled
build split as raw CLI strings: the `find` exclusion, the `--bundle
--external` flags, and the hardcoded license banner are duplicated across
`build:js` and `watch:js` (four copies of the bundle config), and `watch:js`
needs an inner `concurrently` to run two watchers. Every future bundled entry
doubles the duplication. Decision (approved): replace the strings with a node
script holding a declarative exception table.

## The script

`packages/support/assets/scripts/build-js.mjs` — ESM node script, precedent
`scripts/scope-preflight.mjs`: dependency-free, header comment explaining why
the split exists (unbundled files ship import specifiers verbatim through
AssetMapper; bundled entries inline deps consumers don't have importmap
entries for).

- Declarative table at the top:
  `BUNDLED_ENTRIES = { "controllers/dropdown_controller.ts": { external: ["@hotwired/stimulus"], license: "@floating-ui/dom" } }`
  — keys are `src`-relative entry paths. Adding exception N+1 = one entry.
- Default set: recursive readdir walk of `src/**/*.ts` (no glob dependency,
  node-version-safe) minus the table's keys.
- Shared esbuild options: `outdir: "dist"`, `outbase: "src"`,
  `format: "esm"`, sourcemap `linked` (build) / `inline` (watch).
- Build mode: one unbundled `build()` for the default set; one
  `bundle: true` `build()` per exception with its `external` list and
  composed banner. Non-zero exit on any error.
- Watch mode (`--watch` flag): same groups via `context()` + `watch()`,
  single long-lived process — the inner `concurrently` layer is removed.

## License banner (generated, not hardcoded)

`license: "<npm package>"` makes the script compose the banner from the
installed package at build time: `node_modules/<pkg>/package.json` (exact
version + license field) and its `LICENSE` file (copyright line) →
`/*! @floating-ui/dom 1.8.0 | MIT License | Copyright (c) 2021-present Floating UI contributors | https://github.com/floating-ui/floating-ui */`.
Reproducible (inputs are lockfile-pinned), self-correcting on upgrades —
replaces the current hardcoded, stale-prone `^1.8.0` banner text. Unreadable
package.json or LICENSE = hard exit, no silent fallback.

## package.json after

- `"build:js": "node scripts/build-js.mjs"`
- `"watch:js": "node scripts/build-js.mjs --watch"`
- Top-level `watch` (js,css via `concurrently`) unchanged.
- Biome does not govern `scripts/` (scope-preflight precedent) — no lint
  config change.

## Verification

- esbuild JS API surface (`build`, `context`, `banner.js`, `external`,
  `outbase`, watch contexts) verified against the installed esbuild 0.28
  typings before use (no-guessing rule).
- `pnpm build` twice with `git status` clean between runs
  (self-reproducibility of dist).
- Existing grep checks on the bundled output hold: Floating UI inlined,
  exactly one `@hotwired/stimulus` import, banner present.
- `pnpm test:types` + `pnpm test:lint` green; `composer test` untouched.
- dist regenerated and committed in the same change (banner format changes
  once: exact version + copyright line from LICENSE).

## Bookkeeping

- CLAUDE.md: the support build sentence changes to name the script and the
  declarative bundled-entries table.
- No docs/progress row — repo infrastructure, not a Filament port surface.

## Out of scope

- No JS test runner (unchanged policy); the dist gates above are the
  verification.
- No change to CSS build, scope-preflight, or the top-level watch topology.
- No Flex recipe / importmap alternative — revisit only if recipes ship.
