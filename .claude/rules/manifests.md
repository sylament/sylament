---
paths:
  - "/composer.json"
  - "packages/*/composer.json"
  - "packages/*/assets/package.json"
description: What each package manifest declares, when a dependency is a require or a suggest, and which omissions are deliberate.
---
# Manifest rules

- **A dependency inherited through a sibling counts as undeclared.** The one root `vendor/` autoloads the class whether or not the manifest names it, so no gate here fails and the break shows only in the split repository.
- **"Uses" means** a PHP symbol `src/` imports, a Twig tag a shipped template cannot parse without (`{% props %}` needs `symfony/ux-twig-component`), or a PHP extension a call needs (`ext-intl` for `NumberFormatter`), declared in the package that makes the call, since inheriting it is the same split hazard.
- **Unconditional use is a `require`.** An import that some code path always reaches fatals without the package.
- **A guarded optional is a `suggest`.** It is code that is dead without the package (a `class_exists`/`interface_exists`/`instanceof` branch, or a service nothing instantiates without it), so the symbol is never resolved.
- **A guarded optional throws where the user opts in.** Throw a `LogicException` that names the package to install, and never fall through to degraded output, which reads as a working feature.
- **Pin every guard with a test.** A renamed or moved contract dead-ends the branch silently, and no composer-level check sees a `suggest`.
- **A return type forces a `require`.** No guard can stand in front of a method that builds and returns the class, which is why `actions` requires `forms` (`AttachAction::getRecordSelect(): Select`).
- **A cycle is allowed when each side's `src/` imports the other** (`actions`⇄`schemas`, `actions`⇄`forms`); pin a line no other test would miss with a manifest test, as `packages/actions/tests/Architecture/ManifestTest.php` does.
- **Never push a dependency into `support` that support's own code does not use**; every other package would inherit it.
- **Record a non-`symfony/*` grant as a carried decision in `docs/progress/roadmap.md`**, naming which manifest takes it as `require`, `suggest` or root `require-dev`; that is where the next change checks whether a vendor is already signed off.
- **Put a JS peer in both `peerDependencies` and `devDependencies`.** The workspace sets `autoInstallPeers: false` and `strictPeerDependencies: true`, so a peer nobody installs fails the build.

## Deliberate omissions

Each is left undeclared because a package the importer already requires hard-requires it; declare it the moment that host could leave the importer's graph.

- **`twig/twig`** in six packages: `sylament/support` requires it.
- **`symfony/type-info`** in `schemas` (`State\RecordTypeReader`): `symfony/property-info` requires it.
- **`doctrine/dbal`** in `actions`, `schemas`, `tables`: `doctrine/orm` requires `^3.8.2 || ^4`, so import only names both majors carry; how `ParameterType` is used across the two is in `php.md`.
- **`doctrine/persistence`** in `actions`, `tables`: `doctrine/orm` requires it.
- **`doctrine/collections`** in `actions`, `tables`: `doctrine/orm` requires it; declare it first if the ORM ever leaves `actions`, whose `RecordReplicator` instantiates `ArrayCollection` where `tables` only tests `instanceof`.
- **`symfony/polyfill-php86`** (the global `SortDirection` enum) in `schemas`, `tables`: `doctrine/orm` requires it.
- **`psr/container`** in `support`, `panels`: `symfony/service-contracts`, which `support` requires, requires it.
- **`symfony/config`** in `support`, `forms`, `panels`: `symfony/stimulus-bundle`, which `support` and `forms` require, requires it.
- **`symfony/dependency-injection`, `symfony/http-kernel`, `symfony/framework-bundle`** everywhere: every package requires `support`, whose `symfony/stimulus-bundle` requires the first two and whose `symfony/ux-icons` requires framework-bundle, so even `panels`' own `RouteLoaderInterface` import is guaranteed.
- **`league/flysystem`** in `tables` (`ImageColumn`): `sylament/forms` requires it, and only the disk-path arm reaches it.

Background: `docs/internals/dependencies.md`, `docs/internals/monorepo.md`.
