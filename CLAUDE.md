# Sylament

Sylament ports Filament's PHP packages to Symfony: nine bundles under `packages/`, one Composer project, one pnpm workspace.
`support` is the base. `schemas`, `actions` and `forms` require one another, `tables` builds on all three, `notifications` on `actions`, and `panels` composes everything; `infolists` and `widgets` are placeholders.
What has shipped and what comes next is tracked in `docs/progress/roadmap.md`, and this file never restates it.

## Map

| Path | Holds |
| --- | --- |
| `packages/<pkg>/src/` | `Sylament\<Pkg>\` (PSR-4) |
| `packages/<pkg>/tests/` | Pest: `Unit/` and `Integration/` mirror `src/`; `Architecture/` scans files |
| `packages/<pkg>/templates/` | Twig: `components/` are `<twig:>` tags, `partials/` are rendered from PHP |
| `packages/<pkg>/assets/` | pnpm workspace `@sylament/<pkg>`; `dist/` is committed |
| `packages/support/tests/{Doctrine,PHPUnit}/` | test infrastructure the Doctrine suites share (dev-only) |
| `vendor/filament/` | Filament's source: read it before designing anything |
| `docs/principles.md` | the two stances that outrank everything below |
| `docs/progress/` | the port trail: status and reasoning per component (rules in its `README.md`) |
| `docs/internals/` | reasoning behind dev-only infrastructure |
| `../demo` | the peer demo app, its own repo (skill: `demo`) |

## Rules for every task

1. **Filament first.** Read `vendor/filament/<pkg>` before designing, and keep its public class, method and trait names and its closure-injection parameter names; translate only idioms (Eloquent → Doctrine, Livewire → UX LiveComponent, Blade → Twig, `Htmlable` → `Twig\Markup`). A rename passes the gate in `docs/progress/renames.md` first, and `docs/principles.md` outranks this rule.
2. **Dependencies.** `symfony/*` packages are pre-approved; any other vendor needs explicit sign-off first. Each package declares what its own `src/` and `templates/` use, because the split publishes every package alone.
3. **One Composer project.** Never run `composer install` inside `packages/*`: there is one root `vendor/`. Dev tools go in the root `composer.json`. After editing a package's `composer.json`, run `composer update sylament/<pkg>` in each checkout, since features gated on Composer's runtime metadata stay off until you do.
4. **Static analysis.** PHPStan runs at level max with no baseline, and shipped `src/` carries no inline `@phpstan-ignore`: fix the cause.
5. **Comments** state only what the code cannot. Reasoning belongs in `docs/progress/` or `docs/internals/`; never delete reasoning that has no home there yet.
6. **Name arguments** instead of padding defaults to reach a later parameter, in PHP and in Twig.
7. **Reuse before writing.** Check `packages/support/src/helpers.php` before writing Laravel-helper-shaped code, and prefer the construct the language or library ships over manual mechanics.
8. **Same change:** a feature lands with its `docs/progress/` entry, and anything user-facing with its demo showcase card.

## Commands

| Task | Command |
| --- | --- |
| everything on SQLite: Pest per bundle, Rector dry-run, PHPStan, Twig lint | `composer test` |
| one bundle | `composer test:<bundle>` |
| one file | `vendor/bin/pest -c packages/<pkg>/phpunit.dist.xml --test-directory=packages/<pkg>/tests <file>` |
| MySQL, PostgreSQL, the mutation gate | `composer test:mysql`, `composer test:postgresql`, `composer test:mutation` (skill: `server-gates`) |
| one tool | `composer test:static` (PHPStan), `test:lint` (PHP-CS-Fixer), `test:rector`, `test:twig` |
| apply Rector, PHP-CS-Fixer and Twig-CS-Fixer | `composer format` |
| build or watch every asset workspace | `pnpm build`, `pnpm watch` |
| TypeScript, Biome check, Biome fix | `pnpm test:types`, `pnpm test:lint`, `pnpm format` |

## Definition of done

- `composer test` green.
- `composer test:mysql`, `composer test:postgresql` and `composer test:mutation` green at landing; an inner loop may stay on SQLite.
- `pnpm test:types` and `pnpm test:lint` green.
- `composer format` applied; no hand-formatting.
- A user-facing feature updates the demo showcase (cards, descriptions, the "Not built yet" footnotes) before its roadmap row flips.

## Rule files

Rules in `.claude/rules/` load by themselves when you read or edit a file their `paths:` match. A lane working in a worktree outside the repo gets none of them, so its plan task names the files it needs and it reads them first.

While `.claude/rules/` is being filled, read the section of `docs/internals/legacy-claude-md.md` that covers the area you touch.

| File | Paths | Covers |
| --- | --- | --- |
| `docs.md` | `/docs/**` | Where documentation lives and the rules for the port trail. |
| `instructions.md` | `/CLAUDE.md`, `/.claude/**` | How instruction files are placed, scoped, sized and kept free of history. |

## Where a new fact goes

| It is… | It goes to |
| --- | --- |
| needed by every task | this file |
| about certain files | the `.claude/rules/` file whose `paths:` cover them |
| a multi-step procedure | `.claude/skills/<name>/SKILL.md` |
| a reason, an alternative, history | `docs/progress/` or `docs/internals/` |
| status | `docs/progress/roadmap.md` |

Instruction files carry rules, never history; the details are in `.claude/rules/instructions.md`.
