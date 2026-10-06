# The asset workspaces

How the JavaScript and CSS each bundle ships are built, why the build scripts look the way they do, and how a consuming application picks the result up. The rules themselves are in `.claude/rules/frontend.md` and `.claude/rules/css.md`; the styling contract is `docs/internals/styling.md`.

## How do the workspaces fit together?

`pnpm-workspace.yaml` makes every `packages/*/assets` directory that has a `package.json` a workspace named `@sylament/<pkg>`. Bundles without JavaScript (infolists, widgets) keep only `assets/src/styles/` and are not workspaces.

The workspace runs with `hoistPattern: []`, `strictPeerDependencies: true` and `autoInstallPeers: false`. Nothing transitive is reachable by walking up to a parent `node_modules`, so each workspace sees only what its own `package.json` declares. "Module not found" in one workspace means the dependency is missing from that workspace's manifest, not from the root. The root `overrides` pin a single version of Stimulus (`^3`), esbuild, Tailwind, TypeScript and `sortablejs` across every workspace.

PHP and JavaScript are separate dependency graphs. They meet only through Symfony AssetMapper: each bundle's `prependExtension()` maps its `assets/dist` directory under the alias `@sylament/<pkg>`, and only when AssetMapper is installed. The consuming application's importmap resolves bare specifiers; nothing in `packages/*/assets/src` is ever served.

Only `dist/` is published. Every package's `.gitattributes` export-ignores `/assets/src` and `/assets/scripts`, so a split repository carries the built files and nothing they were built from.

## How is a workspace's JavaScript built?

Every workspace ships `scripts/build-js.mjs`, and the seven copies are byte-identical except for their `BUNDLED_ENTRIES` table. The script has three modes:

- **Per-file, unbundled** (the default). Each `src/**/*.ts` compiles to its own file in `dist/`, and import specifiers ship verbatim to the browser. A relative import therefore has to carry `.js`, which Biome's `useImportExtensions` (ts → js) enforces; an extensionless import type-checks and then 404s. A bare specifier has to be one the consuming app's importmap resolves, which is why only `@hotwired/stimulus` and `@symfony/ux-live-component` appear as bare imports in per-file modules.
- **Bundled.** An entry in `BUNDLED_ENTRIES` inlines a third-party dependency the consuming app has no importmap entry for. Its `external` list keeps the framework packages (Stimulus, and UX Live where the entry uses it) as bare specifiers. Its `license` names the package or packages whose notice heads the bundle: `licenseBanner()` composes one `/*! … */` line per package from the installed `package.json` and licence file, so the banner cannot go stale. It looks for `LICENSE`, `LICENSE.md`, then `license`, trims each line, accepts a `Copyright` line without a year, and throws when it finds no version, licence or copyright line.
- **Inlined only.** A module named in an entry's `inlines` exists only inside that bundle and is excluded from the per-file build, because shipped standalone its bare imports would have no importmap entry. Forms' `utils/select.ts`, the port of Filament's select utility, is the one case.

Five of the seven tables are empty (actions, notifications, panels, schemas, tables). Support bundles `@floating-ui/dom` into its `dropdown` controller and `sortablejs` into its `sortable` controller, with Stimulus external. Forms has two entries, both with Stimulus and UX Live external: `select` bundles `@floating-ui/dom` and `sortablejs` and inlines `utils/select.ts`; `file-upload` bundles `filepond`, its eight plugins and `cropperjs`, so ten banner lines head that file. All twelve libraries are MIT. Every one except `sortablejs` ships its own types, which is why support and forms both declare `@types/sortablejs` as a dev dependency. `sortablejs` is therefore bundled twice, once by support and once by forms, and the root override keeps the two copies on one version.

## Why are `live_host.ts` and `cloak.ts` copied between workspaces?

A per-file module that imported another workspace's util would ship a bare `@sylament/<pkg>/…` specifier to the browser, which every consuming app would then have to map in its importmap. Schemas' `exports` map (`"./utils/*.js": "./src/utils/*.ts"`) also points at `src/`, which the split repository export-ignores. So a per-file module copies the util into its own workspace instead:

- `utils/live_host.ts` (resolving the live component that owns an element) lives in schemas and is copied into tables, panels and forms. The first three are byte-identical; forms' copy adds one header comment.
- `utils/cloak.ts` lives in support and is copied into schemas and forms, byte-identical.

The `exports` map is still used, by bundled entries only: forms' `select` and `file-upload` controllers import `@sylament/schemas/utils/schema_component_method.js` (and `file-upload` also `utils/live_host.js`) over a `workspace:*` dev dependency, and esbuild inlines the TypeScript source into the bundle. That is why forms has both a local `live_host.ts` (for its per-file `key-value` and `tags-input` controllers) and the schemas import (for its bundled `file-upload` controller).

## How does a controller reach a consuming application?

A workspace declares its controllers under `symfony.controllers` in its `package.json`, with a `fetch` mode. The application, though, decides what is registered: StimulusBundle's `ControllersMapGenerator::loadUxControllers()` reads the app's `assets/controllers.json`, iterates only the packages and controllers listed there, and skips everything else without a message. A package left out of that file loses its controllers silently: the markup renders, and the behaviour is simply absent. Two omissions cost more than behaviour. Support's `core` controller carries the `autoimport` of `@sylament/support/styles/index.css`, and a disabled controller's autoimports are skipped with it, so an application that disables `core`, or leaves support's block out, loses the whole stylesheet, since every package's rules compile into that one file. And markup a controller uncloaks stays hidden without it: `sy-cloak` is `display: none` whenever scripting is enabled, and only the owning controller's `uncloak()` removes the class, so a `persistCollapsed()` repeater (forms' `repeater`), a `persistCollapsed()` section's body (support's `section`) and `persistTab()` tabs (schemas' `tabs`) never appear when that controller is left out. The per-package consequences are recorded in the trail: `docs/progress/actions/actions-bundle.md` (the page-wide `dispatch` delegator), `docs/progress/tables/tables-bundle.md` (Asset registration), `docs/progress/forms/forms-bundle.md` (`select` and `file-upload`) and `docs/progress/panels/unsaved-changes-alert.md`.

The app's own entry also decides the fetch mode (`fetch` defaults to `eager` there), so the package's `fetch` value is the default a recipe copies, not a guarantee. Every controller is `eager` except forms' `file-upload`:

- Eager is what lets stored state apply on the first paint (panels' sidebar collapse and theme) and arms behaviour before it matters (the unsaved-changes guard).
- An element-less controller has to be eager: `dispatch` binds to no element and adds its capture-phase `document` click listener from `static afterLoad()`, which Stimulus calls at registration, and a lazy controller is registered only when a matching element appears.
- `file-upload` is lazy because FilePond, its plugins and Cropper.js make it by far the heaviest bundle and few pages carry an upload field; the field's own element pulls it in. See `docs/progress/forms/forms-bundle.md`, "The file-upload controller bundles FilePond and is the only lazy controller".

## Why must a controller repaint from the live component's data?

UX Live's morph keeps every input the user has touched as it is, through every re-render, and once a controller writes a LiveProp through the ValueStore (`component.set(name, value, false)`, which sends no request) the store rather than the server-rendered attribute is what the next morph keeps. A controller that writes state this way therefore repaints its controls from `component.getData()` on `connect()` and on every `render:finished`. Tables' `table` and `editable-cell` controllers are the worked cases; the reasoning is in `docs/progress/tables/has-columns.md`, "The cell repaints from the root because a touched control is pinned through every morph".

## How is support's CSS built?

Support's `build` runs `clean → tsc --noEmit → build-js → build:css`. `build:css` is three node steps followed by one Tailwind compile, all inline in the same pnpm script rather than lifecycle hooks:

1. `copy-fonts.mjs` copies the Inter Variable latin woff2 and the package's licence from `@fontsource-variable/inter` into `dist/styles/fonts/`. It resolves its sources with `require.resolve`, which throws when the package or sub-path is gone. That matters because AssetMapper's `CssAssetUrlCompiler` passes an unresolvable `url()` through unchanged behind a log warning (in the default `warn` mode), so a missing font would otherwise surface only as a browser 404. The licence file is served on purpose: the SIL Open Font License requires the notice to travel with the font, and the split publishes only `dist`.
2. `scope-preflight.mjs` regenerates `src/styles/preflight.generated.css` from the installed Tailwind's `preflight.css`, scoped to `.sy` markup.
3. `scope-vendor-css.mjs` regenerates forms' `src/styles/filepond.generated.css` (FilePond and two plugin sheets, under `.sy-fo-file-upload`, keyframes renamed `sy-fo-file-upload-*`) and `src/styles/cropper.generated.css` (Cropper.js, under `.sy-fo-file-upload-editor`, the editor dialog's class, since Cropper's DOM only lives inside it), each headed by a licence banner composed from the installed packages.
4. `tailwindcss -i src/styles/index.css -o dist/styles/index.css --minify`.

Steps 2 and 3 share the selector rewriter `scope-css.mjs`. All three generated sheets match the `.gitignore` pattern `packages/*/assets/src/styles/*.generated.css`, so they are never committed or hand-edited and always match the installed vendor version.

`scope-vendor-css.mjs` lives in support and writes into another workspace for two reasons: postcss is a support dev dependency, and support's `index.css` is the only Tailwind compile in the repository, raw-importing every package's `src/styles/index.css`. Because of `hoistPattern: []`, the vendor packages are installed under forms, so each entry resolves its sources through a `createRequire` rooted at forms' `package.json`. Build order between workspaces is not involved: `pnpm -r` runs support after actions, notifications, panels and schemas, and the sheets are both written and compiled inside support's own `build:css`.

## How does `watch` differ from `build`?

`pnpm watch` runs every workspace's `watch` in parallel; support's runs a JS and a CSS watcher under `concurrently`. Watch output is not build output: `build-js.mjs --watch` writes inline sourcemaps instead of linked ones, and `tailwindcss --watch` omits `--minify`. A watched `dist/` therefore differs from the committed one, and CI's `assets` job, which runs `pnpm build` and fails on any difference, would reject it.

Tailwind's `--watch` also exits as soon as its stdin closes (only `--watch=always` keeps going), and it exits before the first build. In a shell without a TTY, such as an agent's, the CSS half of `pnpm watch` therefore stops at once while the JS half keeps running. Running `pnpm watch` inside `packages/support/assets` instead does not help there: probed directly in the workspace, through `pnpm -r --parallel` and with stdin redirected from `/dev/null`, the watcher exits in under a third of a second every time. Rebuild with `pnpm build` instead.

## Why is `dist/` committed?

The split publishes only `dist/`, and a consuming app installs no Node toolchain, so the built files have to be in the repository. CI's `assets` job installs with `pnpm install --frozen-lockfile`, runs `pnpm test:lint`, `pnpm test:types` and `pnpm build`, and fails when the rebuilt tree differs from the committed one. The committed `pnpm-lock.yaml` is what makes that rebuild reproducible byte for byte (`docs/internals/monorepo.md`).
