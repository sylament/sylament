---
paths:
  - "/composer.json"
  - "packages/*/composer.json"
  - "packages/*/assets/package.json"
  - "packages/*/.gitignore"
description: What each package manifest and ignore file declares, when a dependency is a require or a suggest, and which omissions are deliberate.
---
# Manifest rules

- **A dependency inherited through a sibling counts as undeclared.** The one root `vendor/` autoloads the class whether or not the manifest names it, so no gate here fails and the break shows only in the split repository.
- **"Uses" means** a PHP symbol `src/` imports, a Twig tag a shipped template cannot parse without (`{% props %}` needs `symfony/ux-twig-component`), or a PHP extension a call needs (`ext-intl` for `NumberFormatter`), declared in the package that makes the call, since inheriting it is the same split hazard.
- **Unconditional use is a `require`.** An import that some code path always reaches fatals without the package.
- **A guarded optional is a `suggest`.** It is code that is dead without the package (a `class_exists`/`interface_exists`/`instanceof` branch, or a service nothing instantiates without it), so the symbol is never resolved.
- **A guarded optional throws where the user opts in.** Throw a `LogicException` that names the package to install, and never fall through to degraded output, which reads as a working feature.
- **Pin every guard with a test.** A renamed or moved contract dead-ends the branch silently, and no composer-level check sees a `suggest`.
- **A return type forces a `require`.** No guard can stand in front of a method that builds and returns the class, which is why `actions` requires `forms` (`AttachAction::getRecordSelect(): Select`).
- **A cycle is allowed when each side's `src/` imports the other** (`actions`⇄`schemas`, `actions`⇄`forms`); pin a line no other test would miss with a manifest test, as `tests/Architecture/ManifestTest.php` does.
- **A package that ships a catalogue requires `symfony/translation`, `symfony/translation-contracts`, `symfony/yaml` and `ext-intl`:** the translator loads a YAML catalogue only through `symfony/yaml`, a `+intl-icu` catalogue formats through `ext-intl`, and only a non-dev require makes FrameworkBundle enable the translator by default.
- **Never push a dependency into `support` that support's own code does not use**; every other package would inherit it.
- **Record a non-`symfony/*` grant in the third-party grants table of `docs/internals/dependencies.md`**, naming which manifests take it as `require`, `suggest` or root `require-dev`; that table is where the next change checks whether a vendor is already signed off.
- **Keep every ignore a package's own files need in its `.gitignore`** (`/assets/src/styles/*.generated.css`), never only in the root one: the split publishes each package with its own `.gitignore`, and a standalone checkout of it never sees the root's.
- **Put a JS peer in both `peerDependencies` and `devDependencies`.** The workspace sets `autoInstallPeers: false` and `strictPeerDependencies: true`, so a peer nobody installs fails the build.

## Deliberate omissions

Each but the test tooling is left undeclared because a package the importer already requires hard-requires it; declare it the moment that host could leave the importer's graph.

- **`twig/twig`** in six packages: `sylament/support` requires it.
- **`symfony/type-info`** in `schemas` (`State\RecordTypeReader`): `symfony/property-info` requires it.
- **`doctrine/dbal`** in `actions`, `tables`: `sylament/schemas`, which both require, requires `^4.6`.
- **`doctrine/persistence`** in `actions`, `panels`, `schemas`, `tables`: `doctrine/orm` requires it.
- **`doctrine/collections`** in `actions`, `tables`: `doctrine/orm` requires it; declare it first if the ORM ever leaves `actions`, whose `RecordReplicator` instantiates `ArrayCollection` where `tables` only tests `instanceof`.
- **`symfony/polyfill-php86`** (the global `SortDirection` enum) in `schemas`, `tables`: `doctrine/orm` requires it.
- **`psr/container`** in `support`, `panels`: `symfony/service-contracts`, which `support` requires, requires it.
- **`symfony/config`** in `support`, `forms`, `panels`: `symfony/stimulus-bundle`, which `support` and `forms` require, requires it.
- **`symfony/twig-bridge`** (the `trans` filter) in six packages: `symfony/ux-icons`, which `support` requires, requires `symfony/twig-bundle`, which requires it.
- **`symfony/dependency-injection`, `symfony/http-kernel`, `symfony/framework-bundle`** everywhere: every package requires `support`, whose `symfony/stimulus-bundle` requires the first two and whose `symfony/ux-icons` requires framework-bundle, so even `panels`' own `RouteLoaderInterface` import is guaranteed.
- **`league/flysystem`** in `tables` (`ImageColumn`): `sylament/forms` requires it, and only the disk-path arm reaches it.
- **`psr/log`** in `tables` (`HasColumns`' logger): `symfony/http-kernel`, which support's `symfony/stimulus-bundle` requires, requires it.
- **PHPUnit and Symfony's test tooling** in `src/Testing` (`PHPUnit\Framework\Assert`, `KernelBrowser` and the `browser-kit`, `dom-crawler` and `css-selector` it runs on): never declared, not even as a `suggest`, as Symfony's own `Test\` namespaces leave them undeclared, because only an application's test suite loads that code, and that suite installs them.

Background: `docs/internals/dependencies.md`, `docs/internals/monorepo.md`.
