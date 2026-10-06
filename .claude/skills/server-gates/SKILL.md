---
name: server-gates
description: Use before landing anything that touches Doctrine, to run the MySQL, PostgreSQL and mutation gates.
---
# Server gates

`composer test` runs on in-memory SQLite. The definition of done also needs MySQL, PostgreSQL and the mutation gate green at landing; an inner loop may stay on SQLite.

## 1. Start the servers

```sh
docker compose up -d --wait
```

`compose.yaml` runs MySQL 8.4 on `127.0.0.1:33307` and PostgreSQL 18 on `127.0.0.1:55433`. The ports are non-default on purpose, so another project's server on 3306 or 5432 is never touched; never change them to the defaults.

## 2. Run the whole suite on a server

```sh
composer test:mysql
composer test:postgresql
```

Each sets `SYLAMENT_TEST_DATABASE_URL` and runs the whole `composer test`. Run them one after the other, never side by side (step 4).

## 3. Run one package on one server

```sh
SYLAMENT_TEST_DATABASE_URL='postgresql://postgres:sylament@127.0.0.1:55433/sylament_test?serverVersion=18&charset=utf8' composer test:<package>
SYLAMENT_TEST_DATABASE_URL='mysql://root:sylament@127.0.0.1:33307/sylament_test?serverVersion=8.4.0&charset=utf8mb4' composer test:<package>
```

With the variable unset or empty, a suite runs on in-memory SQLite. The URL must name a database (`…/sylament_test`): the pool derives its own databases from that name.

## 4. Keep server runs serial

- The pool gives each entity manager a test builds a database of its own, named `<base>_<package>_<TEST_TOKEN>_<slot>`. The `TEST_TOKEN` a parallel run sets keeps two processes' databases apart.
- It separates databases, not connections: the pool keeps its own housekeeping connection to every database it has built, open for the life of the process, beside the connections the tests hold.
- PostgreSQL allows 100 connections and every concurrent run shares them, so run one server suite at a time and stop a stale one before starting the next.

## 5. Clean up pool databases

Nothing drops a pool database at the end of a run; each run's `DROP DATABASE IF EXISTS` plus `CREATE DATABASE` makes leftovers harmless but lets them pile up. Clean them when you are done:

```sh
docker compose down -v
```

or drop the `sylament_test_%` databases on the server.

## 6. Run the mutation gate

1. Install pcov once: `brew install shivammathur/extensions/pcov@8.5`.
2. Run `composer test:mutation`. It mutates schemas' `src/Identity/**` on SQLite, since the script clears `SYLAMENT_TEST_DATABASE_URL` itself, with `--parallel`.
3. Its `--min` is committed in the `composer.json` script, so the threshold changes only in a reviewed change. The surviving mutants and why they survive are recorded in `docs/progress/schemas/record-key.md` under "A mutation gate holds the codec".
