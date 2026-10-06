---
name: demo
description: Use when verifying a feature in a browser, or adding or updating a showcase card in the peer demo app at ../demo.
---
# The peer demo

`../demo` is a separate git repo that consumes this monorepo through symlinked path repositories, so a code change here is live there at once. Run every command below from `../demo`, and commit demo changes in that repo, never here.

## 1. Read the map

Read `../demo/CLAUDE.md` first. It holds the layout, the card bases and templates, the admin panel's structure, the app-owned configuration, the commands and the test buckets. It does not load by itself from a session started in this repo.

## 2. Sync and start

1. After changing any `packages/*/composer.json` here, run `composer update "sylament/*"` in `../demo`; path packages' metadata is read from the lock, so the change is invisible until you do.
2. `task up` starts the docker Postgres, the Symfony server and the proxy.
3. `task seed` migrates the **dev** database and seeds it. Run it before every browser pass: `task test` migrates only the test database, so a dev database behind a new migration fails with `SQLSTATE[42703] column … does not exist`, which looks like a code defect and is not one.
4. `task css:watch` keeps Tailwind rebuilt while you edit templates.
5. Run database tools through the Symfony CLI (`symfony console …`, `symfony php vendor/bin/phpunit`), which injects the docker database's random host port.

## 3. Add or update a card

1. Put the class in `src/Twig/Components/Showcase/<Package>/`, extending `App\Showcase\ShowcaseCard` (a schema) or `App\Showcase\TableShowcaseCard` (a table), with the matching chrome template from `templates/components/Showcase/` in its `#[AsLiveComponent]`.
2. Never set component ids by hand to tell cards apart: `ShowcaseCard::getSchema()` prefixes them per card already.
3. Add the card to `templates/pages/showcase/<package>.html.twig`.
4. Update the page's closing "Not built yet" footnote, and any card `description()` that named the feature as unbuilt.
5. Add or extend the test in `tests/Functional/Showcase/` (or `Admin/` for a panel page).
6. Run `task test` in `../demo`, which is the demo's gate.

The showcase demonstrates every user-facing feature, so a feature lands with its card, footnote and descriptions in the same change, and a stale one is a bug.

## 4. Verify in a browser

1. Find the URL: `symfony server:status` prints the local port, which is 8000 only when no other project holds it; `https://sylament.wip` works through the Symfony proxy in a browser that uses its PAC.
2. With Martin's Chrome running, use Claude in Chrome. Otherwise use the Playwright MCP headless against `https://127.0.0.1:<port>`, because the `.wip` domain does not resolve without the proxy's PAC.
3. After editing a demo PHP class mid-pass, wait a few seconds and make one warm-up request: opcache revalidates every 2 seconds (`opcache.revalidate_freq`), so the first request can still run the old code.
4. When counting live requests, scope the count to the card under test by its `data-live-name-value`: the tables page carries a polling card (`DeferredTableCard`, `poll('10s')`) that adds requests of its own.
5. If a CLI database command hangs with "could not initiate GSSAPI security context", prefix it with `PGGSSENCMODE=disable`; sandboxed shells can stall in libpq's GSSAPI negotiation against the docker Postgres.
6. When done, delete `.playwright-mcp/` and any response-body files the Playwright MCP saved in this repo's root. Both repos gitignore `.playwright-mcp`, so this is cleanup, not a commit guard.

## 5. Reset

`symfony console app:demo:seed --fresh` truncates and reseeds, which undoes what cards over `query()` and the admin pages wrote during the pass.
