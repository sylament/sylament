# build-js.mjs Structured Build Orchestration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the duplicated per-file/bundled esbuild CLI strings in `packages/support/assets/package.json` with one node script holding a declarative bundled-entries table.

**Architecture:** `scripts/build-js.mjs` (ESM, dependency-free beyond esbuild, `scope-preflight.mjs` precedent) partitions `src/**/*.ts` into a default unbundled group plus `BUNDLED_ENTRIES` exceptions, builds each group through esbuild's JS API, composes each exception's license banner from the installed package at build time, and switches every group to watch contexts under `--watch` — removing the inner `concurrently` layer.

**Tech Stack:** Node v26 (`fs.readdirSync(dir, { recursive: true })` available), esbuild ^0.28 JS API (`build`, `context`, `BuildOptions.banner/external/outbase`, `sourcemap: 'linked' | 'inline'` — all verified in the installed `node_modules/esbuild/lib/main.d.ts`), `import * as esbuild from 'esbuild'` (CJS interop-safe).

Spec: `docs/superpowers/specs/2026-07-27-build-js-script-design.md`.

## Global Constraints

- The dist contract is unchanged in SET: the same 5 `.js` outputs (`controllers/core_controller.js`, `controllers/dropdown_controller.js`, `controllers/modal_controller.js`, `controllers/section_controller.js`, `utils/cloak.js`) plus their 5 committed `.js.map` files (build mode = `sourcemap: 'linked'`). Bundled-entry bytes change once (new banner format); everything else must stay byte-identical.
- Bundled output invariants (grep-verifiable): Floating UI implementation inlined (`computePosition` present), exactly one `import` line (`@hotwired/stimulus`), banner comment present at the top.
- Banner format, composed from the installed package (hard exit if unreadable, no fallback): `/*! <name> <version> | <license> License | <copyright line from LICENSE> | <homepage> */` → currently `/*! @floating-ui/dom 1.8.0 | MIT License | Copyright (c) 2021-present Floating UI contributors | https://floating-ui.com */` (verified against `node_modules/@floating-ui/dom`: version `1.8.0`, license `MIT`, LICENSE line 3 `Copyright (c) 2021-present Floating UI contributors`, homepage `https://floating-ui.com`).
- Biome does not govern `scripts/` — do not add lint config; match `scope-preflight.mjs` style (4-space indent, `node:` imports, single quotes).
- No new dependencies of any kind.
- Commit messages: conventional, ending with the `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>` trailer. CLAUDE.md updated in the same commit as the behavior it describes.
- There is no JS test runner and none may be added; verification is the gates listed per task.

---

### Task 1: The script, build mode, and `build:js` rewiring

**Files:**
- Create: `packages/support/assets/scripts/build-js.mjs`
- Modify: `packages/support/assets/package.json` (the `build:js` line only — `watch:js` stays on the old string until Task 2)
- Modify: committed `packages/support/assets/dist/` (regenerated; bundled entry's bytes change with the new banner)

**Interfaces:**
- Consumes: esbuild JS API (`esbuild.build(options)`, options `entryPoints: string[]`, `outdir`, `outbase`, `format: 'esm'`, `sourcemap: 'linked'`, `bundle: true`, `external: string[]`, `banner: { js: string }`).
- Produces: `node scripts/build-js.mjs` (build mode) and the script's internals Task 2 extends — `BUNDLED_ENTRIES` table, `licenseBanner(packageName): string`, the `groups` array, and the `watch` boolean already parsed from `--watch` (Task 2 implements the watch branch; in this task `--watch` exits with an error).

- [ ] **Step 1: Write the script**

`packages/support/assets/scripts/build-js.mjs`:

```js
/**
 * Builds src/**\/*.ts into dist/ for AssetMapper consumption.
 *
 * Two shipping modes, driven by BUNDLED_ENTRIES:
 *   - default: per-file, unbundled — import specifiers ship verbatim to the
 *     browser (relative imports carry .js; bare specifiers resolve through the
 *     consuming app's importmap, e.g. @hotwired/stimulus);
 *   - bundled: entries with a dependency consumers have no importmap entry
 *     for — the dependency is inlined, its license banner composed from the
 *     installed package so it can never go stale, and framework packages are
 *     kept external.
 *
 * Usage: node scripts/build-js.mjs [--watch]
 */

import fs from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';

/** src-relative entry → { external: bare specifiers left to the importmap, license: package whose notice heads the bundle }. */
const BUNDLED_ENTRIES = {
    'controllers/dropdown_controller.ts': {
        external: ['@hotwired/stimulus'],
        license: '@floating-ui/dom',
    },
};

const here = path.dirname(fileURLToPath(import.meta.url));
const srcDir = path.join(here, '../src');
const watch = process.argv.includes('--watch');
const require = createRequire(import.meta.url);

const sharedOptions = {
    outdir: path.join(here, '../dist'),
    outbase: srcDir,
    format: 'esm',
    sourcemap: watch ? 'inline' : 'linked',
};

function licenseBanner(packageName) {
    const packageJsonPath = require.resolve(`${packageName}/package.json`);
    const { version, license, homepage } = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    const licenseText = fs.readFileSync(path.join(path.dirname(packageJsonPath), 'LICENSE'), 'utf8');
    const copyright = licenseText.split('\n').find((line) => line.startsWith('Copyright'));
    if (!version || !license || !copyright) {
        throw new Error(`Cannot compose the license banner for ${packageName}: missing version, license, or a "Copyright" line in LICENSE.`);
    }

    return `/*! ${packageName} ${version} | ${license} License | ${copyright.trim()}${homepage ? ` | ${homepage}` : ''} */`;
}

const allEntries = fs
    .readdirSync(srcDir, { recursive: true })
    .filter((file) => file.endsWith('.ts'))
    .map((file) => file.split(path.sep).join('/'))
    .sort();

for (const bundled of Object.keys(BUNDLED_ENTRIES)) {
    if (!allEntries.includes(bundled)) {
        throw new Error(`BUNDLED_ENTRIES names ${bundled}, which does not exist under src/.`);
    }
}

const defaultEntries = allEntries.filter((file) => !(file in BUNDLED_ENTRIES));

const groups = [
    { ...sharedOptions, entryPoints: defaultEntries.map((file) => path.join(srcDir, file)) },
    ...Object.entries(BUNDLED_ENTRIES).map(([file, { external, license }]) => ({
        ...sharedOptions,
        entryPoints: [path.join(srcDir, file)],
        bundle: true,
        external,
        banner: { js: licenseBanner(license) },
    })),
];

if (watch) {
    throw new Error('--watch is not implemented yet.');
}

await Promise.all(groups.map((group) => esbuild.build(group)));
```

Notes baked into the code above (do not deviate): entries are sorted so group composition is deterministic; `readdirSync(..., { recursive: true })` returns platform-separator paths, normalized to `/` before comparing against the table keys; an unknown table key is a hard error (fail fast — a renamed controller must not silently fall back to unbundled); a top-level-await rejection exits the process non-zero, which is the error contract.

- [ ] **Step 2: Run the script and verify equivalence with the old build**

```bash
cd packages/support/assets
rm -rf dist && node scripts/build-js.mjs
find dist -type f | sort
```

Expected: exactly 10 files — the 5 `.js` outputs and 5 `.js.map` files listed in Global Constraints. Then verify the bundled invariants:

```bash
grep -c "computePosition" dist/controllers/dropdown_controller.js        # >= 1
grep -c "^import" dist/controllers/dropdown_controller.js                # exactly 1
head -1 dist/controllers/dropdown_controller.js                          # the banner: /*! @floating-ui/dom 1.8.0 | MIT License | Copyright (c) 2021-present Floating UI contributors | https://floating-ui.com */
grep -c "sourceMappingURL=core_controller.js.map" dist/controllers/core_controller.js  # exactly 1 (linked maps preserved)
```

And byte-equivalence of the untouched outputs against the committed dist (only the bundled entry + its map may differ, due to the banner):

```bash
git -C ../../.. status --porcelain packages/support/assets/dist
```

Expected: modifications ONLY to `dist/controllers/dropdown_controller.js` and `dist/controllers/dropdown_controller.js.map`. If any other file shows as modified, the script's options diverge from the old CLI flags — stop and fix before proceeding.

- [ ] **Step 3: Rewire `build:js`**

In `packages/support/assets/package.json`, replace the `build:js` value (leave `watch:js` untouched in this task):

```json
"build:js": "node scripts/build-js.mjs",
```

- [ ] **Step 4: Verify the full pipeline and self-reproducibility**

From the repo root:

```bash
pnpm build && git status --porcelain
pnpm build && git status --porcelain   # second run: must print nothing new
pnpm test:types && pnpm test:lint
```

Expected: first run leaves only the two dropdown dist files modified (to be committed); second run changes nothing further (byte-reproducible); types and lint green.

- [ ] **Step 5: Verify `--watch` fails loudly (not silently)**

```bash
cd packages/support/assets && node scripts/build-js.mjs --watch; echo "exit: $?"
```

Expected: error message `--watch is not implemented yet.` and a non-zero exit code.

- [ ] **Step 6: Commit**

```bash
git add packages/support/assets/scripts/build-js.mjs packages/support/assets/package.json packages/support/assets/dist
git commit -m "build(support): move build:js orchestration into scripts/build-js.mjs

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Watch mode, `watch:js` rewiring, CLAUDE.md

**Files:**
- Modify: `packages/support/assets/scripts/build-js.mjs` (replace the `--watch` error branch)
- Modify: `packages/support/assets/package.json` (the `watch:js` line)
- Modify: `CLAUDE.md` (the support asset-build sentence in Commands, and the Conventions sentence describing the bundled entry)

**Interfaces:**
- Consumes: Task 1's `groups` array and `watch` flag; esbuild `context(options)` → `ctx.watch()` (verified present in the installed typings: `export declare function context<T>(options): Promise<BuildContext<T>>`).
- Produces: `node scripts/build-js.mjs --watch` — single process watching all groups with inline sourcemaps.

- [ ] **Step 1: Implement the watch branch**

In `build-js.mjs`, replace:

```js
if (watch) {
    throw new Error('--watch is not implemented yet.');
}

await Promise.all(groups.map((group) => esbuild.build(group)));
```

with:

```js
if (watch) {
    const contexts = await Promise.all(groups.map((group) => esbuild.context(group)));
    await Promise.all(contexts.map((ctx) => ctx.watch()));
    console.log(`[build-js] watching ${allEntries.length} entries (${Object.keys(BUNDLED_ENTRIES).length} bundled)`);
} else {
    await Promise.all(groups.map((group) => esbuild.build(group)));
}
```

(`ctx.watch()` performs the initial build and keeps the process alive through esbuild's service; Ctrl-C ends it — same lifecycle as the old `--watch=forever` CLI processes.)

- [ ] **Step 2: Smoke-test watch mode deterministically**

```bash
cd packages/support/assets
rm -f dist/utils/cloak.js
timeout 8 node scripts/build-js.mjs --watch; echo "exit: $?"
grep -c "sourceMappingURL=data:" dist/utils/cloak.js
ls dist/utils/cloak.js.map 2>/dev/null || echo "no map file (correct for inline)"
```

Expected: the process runs until `timeout` kills it (exit 124 — proving it stayed alive as a watcher), `dist/utils/cloak.js` was regenerated by the initial watch build, its sourcemap is inline (`sourceMappingURL=data:` present, count 1), and no `.js.map` file was created for it. Note: watch mode writes inline-sourcemap outputs into dist — that is pre-existing behavior from the old `watch:js` (inline sourcemaps overwrite dist during development); restore the committed dist afterwards:

```bash
git checkout -- dist
```

- [ ] **Step 3: Rewire `watch:js`**

In `packages/support/assets/package.json`, replace the `watch:js` value:

```json
"watch:js": "node scripts/build-js.mjs --watch",
```

- [ ] **Step 4: Update CLAUDE.md**

Two sentences, minimal edits:
- Commands (JS/CSS section): the sentence describing `support`'s asset build — rewrite the part naming the per-file esbuild pass and the bundled dropdown invocation so it says the JS build runs through `assets/scripts/build-js.mjs`, which builds every `src/**/*.ts` per-file/unbundled except the entries in its `BUNDLED_ENTRIES` table (today: the dropdown controller, bundling `@floating-ui/dom` with Stimulus external and a license banner composed from the installed package).
- Conventions (TS bullet): the sentence about `@floating-ui/dom` being bundled — update its mechanism reference from the inline `--bundle` flags to the `BUNDLED_ENTRIES` table in `scripts/build-js.mjs`, keeping the "never resolved by consumers" statement.

- [ ] **Step 5: Full gates**

From the repo root:

```bash
pnpm build && git status --porcelain    # must print nothing (dist already committed in Task 1)
pnpm test:types && pnpm test:lint
composer test:static
```

Expected: all green, no dist drift.

- [ ] **Step 6: Commit**

```bash
git add packages/support/assets/scripts/build-js.mjs packages/support/assets/package.json CLAUDE.md
git commit -m "build(support): watch mode for build-js.mjs, drop inner concurrently layer

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```
