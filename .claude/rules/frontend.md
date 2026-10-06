---
paths:
  - "packages/*/assets/**/*.{ts,mjs,json}"
  - "/package.json"
  - "/pnpm-workspace.yaml"
  - "/tsconfig.base.json"
  - "/biome.json"
description: TypeScript, the asset workspaces, the JS build script and how Stimulus controllers ship.
---
# Frontend rules

- **Declare every import in the importing workspace's own `package.json`;** `hoistPattern: []` blocks parent-walking, so "Module not found" means that manifest lacks it.
- **Reach PHP only through AssetMapper,** which maps each bundle's `assets/dist` as `@sylament/<pkg>`; the two dependency graphs share nothing else.
- **Keep `tsconfig.base.json` the only compiler config** (strict, ES2022, ESM, bundler resolution); every workspace `tsconfig.json` just extends it.
- **Write relative imports with the compiled `.js` extension;** the per-file build ships specifiers verbatim, so an extensionless import type-checks and then 404s.
- **Never write `any` or a non-null `!`, and use `import type` and `const`;** Biome fails on all four.
- **Biome checks only `packages/*/assets/src/**/*.{ts,js}`,** so `scripts/*.mjs` keep their existing style by hand.
- **Leave `@hotwired/stimulus` (a `^3` peer, pinned by the root override) and `@symfony/ux-live-component` as bare, external specifiers;** the consuming app's importmap resolves them.
- **Bundle any other third-party library through `BUNDLED_ENTRIES`** with its package in `license`; consumers have no importmap entry for it, and the banner is composed from the installed package so it cannot go stale.
- **Name a module in its entry's `inlines`** when it imports a bundled-only library; shipped per-file, its bare import would resolve nowhere.
- **Change `scripts/build-js.mjs` in all seven workspaces at once;** the copies are identical except for their `BUNDLED_ENTRIES` table.
- **Copy a cross-workspace util into a per-file module's own workspace** (as `utils/live_host.ts` and `utils/cloak.ts` are), and keep the copies in step with the original; a bare `@sylament/<pkg>/…` import would need an importmap entry, and schemas' `exports` map points at the export-ignored `src/`.
- **Import `@sylament/schemas/utils/*.js` only from a bundled entry** (over a `workspace:*` dev dependency); esbuild inlines that TypeScript source, which a per-file build cannot.
- **After a new controller lands, add it to the demo's `assets/controllers.json`;** StimulusBundle registers only what that file lists and skips the rest without an error.
- **Declare a new controller `fetch: eager`;** a lazy one registers only when its element appears, which an element-less one like `dispatch` never has.
- **Make a controller `lazy` only when its bundle is heavy and its element rare,** as `file-upload` (FilePond) is; everything else loads up front.
- **Repaint a controller's controls from `component.getData()` on `connect()` and every `render:finished`** once it writes LiveProps through `component.set(…, false)`; after a morph the store, not the server-rendered attribute, wins, and UX keeps every touched input as it was.
- **Rebuild `dist/` with `pnpm build` and commit it with the source;** CI's `assets` job rebuilds it and fails on any difference.
- **Never commit `dist/` written by `pnpm watch`;** watch emits inline sourcemaps and unminified CSS, which differ from the build.
- **Let one task own the `dist/` rebuild when work runs in parallel;** every rebuild rewrites the same tree.
- **Commit `pnpm-lock.yaml` with the manifest change;** CI installs with `--frozen-lockfile`.
- **Without a TTY, rebuild with `pnpm build` instead of watching;** Tailwind's `--watch` exits when stdin closes, so the CSS half of `pnpm watch` stops before its first build, inside the workspace too.
- **Repaint a server-canonical control** from its root's authoritative value on `connect()` and after every render; UX pins every touched input through every morph, so the repaint is the only correction.

Background: `docs/internals/assets.md`, `docs/progress/forms/forms-bundle.md`, `docs/progress/tables/has-columns.md`.
