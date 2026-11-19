---
name: server-gates
description: Use before landing anything that touches Doctrine, to run the MySQL and PostgreSQL gates.
---
# Server gates

`composer test` runs the root suite on SQLite, a file per run. The definition of done also needs MySQL and PostgreSQL green at landing; an inner loop may stay on SQLite.

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

Each sets `SYLAMENT_TEST_DATABASE_URL` and runs `composer test:pest`, the root suite, and not PHP-CS-Fixer, Rector, PHPStan or Twig lint, which no database changes. Run them one after the other, never side by side (step 4).

## 3. Run one package on one server

```sh
SYLAMENT_TEST_DATABASE_URL='postgresql://postgres:sylament@127.0.0.1:55433/sylament_test?serverVersion=18&charset=utf8' vendor/bin/pest tests/Packages/<Pkg>
SYLAMENT_TEST_DATABASE_URL='mysql://root:sylament@127.0.0.1:33307/sylament_test?serverVersion=8.4.0&charset=utf8mb4' vendor/bin/pest tests/Packages/<Pkg>
```

With the variable unset or empty, the suite runs on SQLite. The URL must name a database (`…/sylament_test`): the pool derives its own databases from that name. The run is one process, since `TestDatabaseExtension` refuses `--parallel`.

## 4. Keep server runs serial

- For the root suite the pool keeps one database per worker, `<base>_suite_<TEST_TOKEN>`; a run that sets `SYLAMENT_TEST_RUN` gets `_<run>` after `<base>`. `SYLAMENT_TEST_RUN` keeps two runs' databases apart.
- It separates databases, not connections: the pool keeps its own housekeeping connection to every database it has built, open for the life of the process, beside the connections the tests hold. A root-suite run holds about three.
- PostgreSQL allows 100 connections and every concurrent run shares them, so run one server suite at a time and stop a stale one before starting the next.

## 5. Clean up pool databases

Nothing drops a pool database at the end of a run; each run's `DROP DATABASE IF EXISTS` plus `CREATE DATABASE` makes leftovers harmless but lets them pile up. Clean them when you are done:

```sh
docker compose down -v
```

or drop the `sylament_test_%` databases on the server.
