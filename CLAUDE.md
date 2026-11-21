# Sylament

Sylament ports Filament's PHP packages to Symfony: nine bundles under `packages/`, one Composer project, one pnpm workspace.
`support` is the base. `schemas`, `actions` and `forms` require one another, `tables` builds on all three, `notifications` on `actions`, and `panels` composes everything; `infolists` and `widgets` are placeholders.
A component's status is its row in the port trail; what to build next is decided outside the repository, and this file restates neither.

## Map

| Path | Holds |
| --- | --- |
| `packages/<pkg>/src/` | `Sylament\<Pkg>\` (PSR-4) |
| `packages/<pkg>/templates/` | Twig: `components/` are `<twig:>` tags, `partials/` are rendered from PHP |
| `packages/<pkg>/assets/` | pnpm workspace `@sylament/<pkg>` (the placeholders hold only a stylesheet); `dist/` is committed |
| `packages/<pkg>/translations/` | its catalogues: `<domain>+intl-icu.en.yaml` under Filament's keys, `validators.en.yaml` (rules: `.claude/rules/catalogues.md`) |
| `tests/` | the root Pest suite, `Sylament\Tests\` (PSR-4): one kernel and one base class for every package |
| `tests/Packages/` | each package's tests: `tests/Packages/<Pkg>/` mirrors `packages/<pkg>/src/` |
| `tests/Fixtures/` | its kernel and per-area fixtures, laid out as a Symfony app: `src/`, `config/`, `templates/`, `assets/`, `translations/` |
| `tests/Infrastructure/` | the machinery every test shares, and in `{Doctrine,PHPUnit}/` the Doctrine rig (dev-only) |
| `tests/Architecture/` | the scans of the repository's files, the translation gates' scans (`Translations/`), the rule hook (`Rules/`) and the port-trail tools (`Trail/`) |
| `vendor/filament/` | Filament's source: read it before designing anything |
| `docs/progress/` | the port trail: status and reasoning per component (rules: `.claude/rules/trail.md`) |
| `docs/internals/` | the reasoning behind the rules, and the dev-only infrastructure |
| `../demo` | the peer demo app, its own repo (skill: `demo`) |

## Rules for every task

Two principles outrank every rule below; their tests and worked cases are in `docs/internals/principles.md`.

- **Symfony idiom behind Filament's API.** Keep what the user types, and build what is behind it the Symfony way: a dependency arrives through the constructor or `#[Required]`, never a facade, service locator, global or static reach into a service, and no API hides from PHPStan (`__call()`, magic properties), because a Laravel mechanism does not become acceptable by arriving through Filament; a Laravel name is fine.
- **The ecosystem before invention.** Use a Symfony package, wrap it when its API differs from ours, or add only the piece it lacks beside it, because its maintainers then carry the rest; replace one only for a named, checkable gap.

1. **Filament first.** Read `vendor/filament/<pkg>` before designing, and keep its public class, method and trait names and its closure-injection parameter names; translate only idioms (Eloquent → Doctrine, Livewire → UX LiveComponent, Blade → Twig, `Htmlable` → `Twig\Markup`, `__()` → an injected translator). A rename passes the gate in `docs/progress/renames.md` first, and the two principles above outrank this rule.
2. **Dependencies.** Build on what the Symfony team maintains, the stack it sits on (Twig, Doctrine) and established tooling (Pest, PHPStan); outside that circle, write our own code rather than adopt a package. `symfony/*` packages are pre-approved; any other vendor needs explicit sign-off first. Each package declares what its own `src/` and `templates/` use, because the split publishes every package alone.
3. **One Composer project.** Never run `composer install` inside `packages/*`: there is one root `vendor/`. Dev tools go in the root `composer.json`. After editing a package's `composer.json`, run `composer update sylament/<pkg>` in each checkout, since features gated on Composer's runtime metadata stay off until you do.
4. **Static analysis.** PHPStan runs at level max and shipped `src/` carries no inline `@phpstan-ignore`: fix the cause, and put an error that genuinely cannot be fixed in a baseline file, where it is counted and reviewable.
5. **Comments** state only what the code cannot. Reasoning belongs in `docs/progress/` or `docs/internals/`; never delete reasoning that has no home there yet.
6. **Name arguments** instead of padding defaults to reach a later parameter, in PHP and in Twig.
7. **Reuse before writing.** Check `packages/support/src/helpers.php` before writing Laravel-helper-shaped code, and prefer the construct the language or library ships over manual mechanics.
8. **Same change:** a feature lands with its `docs/progress/` entry.

## Commands

| Task | Command |
| --- | --- |
| everything on SQLite: the root suite, PHP-CS-Fixer and Rector dry-runs, PHPStan, Twig lint | `composer test` |
| the root suite in parallel, in one process; one package of it, one file of it | `composer test:pest`, `XDEBUG_MODE=off vendor/bin/pest`; `vendor/bin/pest tests/Packages/<Pkg>`, `vendor/bin/pest tests/Packages/<Pkg>/<path>Test.php` |
| MySQL, PostgreSQL: `composer test:pest` on a server | `composer test:mysql`, `composer test:postgresql` (skill: `server-gates`) |
| one tool | `composer test:static` (PHPStan), `test:lint` (PHP-CS-Fixer), `test:rector`, `test:twig` |
| apply Rector, PHP-CS-Fixer and Twig-CS-Fixer | `composer format` |
| regenerate the port-trail indexes | `composer docs:index` |
| build or watch every asset workspace | `pnpm build`, `pnpm watch` |
| TypeScript, Biome check, Biome fix | `pnpm test:types`, `pnpm test:lint`, `pnpm format` |

## Definition of done

- `composer update` run before landing, its fallout fixed in a commit of its own: CI resolves fresh, so a stale `vendor/` hides what CI will fail on (`docs/internals/monorepo.md`).
- `composer test` green.
- `composer test:mysql` and `composer test:postgresql` green at landing; an inner loop may stay on SQLite.
- `pnpm test:types` and `pnpm test:lint` green.
- `composer format` applied; no hand-formatting.
- A user-facing feature updates the demo showcase (cards, descriptions, the "Not built yet" footnotes) in the same change, before its trail row flips to done; the testing API in `src/Testing` gets no card, because the demo is a dev-only app that runs no application's tests.
- Closing a milestone: run the `docs-audit` skill.
- Instruction files current: a change that makes a rule or skill wrong, or sets a convention for files no rule covers, updates or creates the `.claude/rules/` or `.claude/skills/` file in the same change (shape: `.claude/rules/instructions.md`).

## Rule files

Rules in `.claude/rules/` load after any tool call that touches a file their `paths:` match: Claude Code loads them for Read, Edit, Write and one plain view of one file (`cat`, `sed -n`, `grep -n`, alone or after `cd … &&`), and the rule hook (`tests/Architecture/Rules/`) for every other Bash command. A rule arrives after the call that touched its files, so read it before you create a file it covers. A lane working in a worktree outside the repo gets none of them, so its plan task names the files it needs and it reads them first.

| File | Paths | Covers |
| --- | --- | --- |
| `bundles.md` | `packages/*/src/*Bundle.php`, `packages/*/src/DependencyInjection/**` | How a bundle class wires its services, config tree, asset path and component registration. |
| `catalogues.md` | `packages/*/translations/**` | How a package's translation catalogues are named, keyed and written, which domains a package may name, and how validator messages differ. |
| `colors.md` | `packages/*/src/Colors/**`, `packages/support/src/Twig/Extension/ColorExtension.php` | How a colour is named, how a contrast policy picks its shade, and how policies are registered. |
| `css.md` | `packages/*/assets/src/styles/**` | How component stylesheets are written, scoped and coloured, and what support's CSS build generates. |
| `docs.md` | `/docs/progress/**`, `/docs/internals/**` | Where documentation lives, and the rules every docs file follows. |
| `frontend.md` | `packages/*/assets/**/*.{ts,mjs,json}`, `/package.json`, `/pnpm-workspace.yaml`, `/tsconfig.base.json`, `/biome.json` | TypeScript, the asset workspaces, the JS build script and how Stimulus controllers ship. |
| `instructions.md` | `/CLAUDE.md`, `/.claude/**` | How instruction files are placed, scoped, sized and kept free of history. |
| `manifests.md` | `/composer.json`, `packages/*/composer.json`, `packages/*/assets/package.json`, `packages/*/.gitignore` | What each package manifest and ignore file declares, when a dependency is a require or a suggest, and which omissions are deliberate. |
| `php.md` | `packages/*/src/**/*.php` | How shipped PHP is written, from closure evaluation and typing to construction, static instances, trait placement, record identity, helpers and comments. |
| `test-database.md` | `/tests/Fixtures/src/Entity/**`, `/tests/Infrastructure/Doctrine/**`, `/tests/Fixtures/src/*/Doctrine/**`, `/tests/Fixtures/config/packages/*.php` | How the root suite prepares its database, where its entities and Doctrine fixtures live, and how it counts statements and cleans up after a server test. |
| `test-infrastructure.md` | `/tests/Fixtures/**`, `/tests/Infrastructure/**`, `/tests/TestCase.php`, `/tests/TestCaseTest.php`, `/tests/Pest.php`, `/phpunit.dist.xml` | How the root suite's kernel, configuration, fixture areas, writable paths, database and base class are laid out and kept apart between concurrent runs. |
| `test-locales.md` | `/tests/Fixtures/translations/**`, `/tests/Fixtures/config/routes/*.php` | How a fixture names its own domain and overrides a shipped one for one locale, how a test reaches a key in the default domain, and where a localised live route lives. |
| `test-renders.md` | `/tests/Packages/**`, `/tests/Fixtures/src/*/Selectors.php` | How a root-suite test queries a render, what it asserts first, and where a selector it shares is defined. |
| `test-scope.md` | `/tests/Packages/**` | How much a package test covers, how a bug is pinned, and what never gets a test. |
| `testing-api.md` | `packages/*/src/Testing/**` | When a testing-API helper is ported, what visibility and status it keeps, how it guards a suggested package, and what stays out of it. |
| `tests.md` | `/tests/**` | Where a root-suite test lives and what kind it is. |
| `trail.md` | `/docs/progress/**` | How a port-trail file is shaped, where status lives, what each file owns, and how its prose and tables are written. |
| `twig.md` | `packages/*/templates/**` | Template layout, component invocation, props and attribute rendering in the bundles' Twig templates. |

## Where a new fact goes

| It is… | It goes to |
| --- | --- |
| needed by every task | this file |
| about certain files | the `.claude/rules/` file whose `paths:` cover them, or a new one |
| a multi-step procedure | `.claude/skills/<name>/SKILL.md` |
| a reason, an alternative, history | `docs/progress/` or `docs/internals/` |
| a component's status | its row in `docs/progress/` |
| a plan, a backlog, what to build next | outside the repository |
| a how-to for application developers | not written yet: the behaviour goes in its trail entry |

Instruction files carry rules, never history; the details are in `.claude/rules/instructions.md`.
