# The monorepo

How the nine bundles share one Composer project, why the two lockfiles are treated differently, and what the split to per-package repositories requires.

## One vendor through path repositories

The repository root is the only Composer project. Its `composer.json` declares one path repository, `packages/*`, and requires every bundle as `self.version`, so Composer symlinks each package into the root `vendor/` and resolves the whole graph once. There is one lockfile and one `vendor/`. A `packages/*/vendor/` or `packages/*/composer.lock` is leftover cruft from running Composer inside a package; delete it.

Sub-package manifests carry production `require` entries only, meaning their real inter-bundle and third-party dependencies. Every dev tool goes in the root `require-dev`.

## Lockfile policy

The root `composer.lock` is gitignored. This is a library monorepo: resolution floats within the declared constraints, and CI resolves fresh, which is what consumers of the split packages do too. A consequence worth knowing is that two checkouts can resolve different dev-tool versions. CI failures that do not reproduce locally usually come from newer tools or runtime libraries.

`pnpm-lock.yaml` is committed, because the pinned JavaScript toolchain must rebuild the committed `assets/dist` byte-for-byte. CI checks that it does.

## The split

Each push to `main`, and each tag, is split into read-only per-package repositories under the `sylament` organisation by the `split-monorepo` job in `.github/workflows/ci.yml`. The job runs only after five gate jobs pass: `php`, `php-mysql` and `php-postgresql` (the suites on each platform), `mutation`, and `assets` (the committed `dist` against a fresh build).

Every package must therefore be self-contained. It declares its own dependencies, because a split repository cannot inherit them through a sibling. Each package's `.gitattributes` export-ignores `/tests`, `/phpunit.dist.xml`, `/assets/src` and `/assets/scripts`. Split-repository tests cannot run standalone, since the Pest bootstrap needs the root `vendor/`, and that is accepted.

## Editing a package manifest

After editing a package's `composer.json`, run `composer update sylament/<pkg>`. A plain `dump-autoload` reads the stale lock metadata that Composer keeps for path packages.

Run it in each checkout separately. Symfony's `ContainerBuilder::willBeAvailable()` decides whether a feature is enabled by reading Composer's `InstalledVersions` runtime metadata. A checkout whose `vendor/composer/installed.json` predates the manifest change keeps such features switched off, and nothing reports it.
