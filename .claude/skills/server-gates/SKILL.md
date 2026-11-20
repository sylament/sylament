---
name: server-gates
description: Use before landing anything that touches Doctrine, to run the MySQL and PostgreSQL gates.
---
# Server gates

`composer test` runs the root suite on SQLite, in parallel; `CLAUDE.md`'s definition of done also needs the two server gates below green at landing.

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

Each sets `SYLAMENT_TEST_DATABASE_URL` and runs `composer test:pest`, the root suite in parallel, and not PHP-CS-Fixer, Rector, PHPStan or Twig lint, which no database changes. Run them one after the other, never side by side (step 4).

## 3. Run one package on one server

```sh
XDEBUG_MODE=off SYLAMENT_TEST_DATABASE_URL='postgresql://postgres:sylament@127.0.0.1:55433/sylament_test?serverVersion=18&charset=utf8' vendor/bin/pest tests/Packages/<Pkg>
XDEBUG_MODE=off SYLAMENT_TEST_DATABASE_URL='mysql://root:sylament@127.0.0.1:33307/sylament_test?serverVersion=8.4.0&charset=utf8mb4' vendor/bin/pest tests/Packages/<Pkg>
```

With the variable unset or empty, the suite runs on SQLite. The URL must name a database (`…/sylament_test`): the run derives its own databases' names from it. Each runs in one process; add `--parallel` to split it across workers.

## 4. Keep server runs serial

- A run in one process has one database, `<base>_<run>`, where `<run>` is `SYLAMENT_TEST_RUN` or `default`, and each parallel worker its own, `<base>_<run>-<TEST_TOKEN>`, so `SYLAMENT_TEST_RUN` keeps two runs' databases apart; the run lock guards one checkout only, so a server run from a worktree beside one from the main checkout also needs its own `SYLAMENT_TEST_RUN`, or each drops the other's databases.
- It separates databases, not connections: `ServerDatabase` keeps a server connection and a housekeeping connection open for the life of each process, beside the connections the tests hold. A worker holds two or three, so `composer test:pest`'s cap of 8 workers keeps a run near 21.
- PostgreSQL allows 100 connections and every concurrent run shares them, so run one server suite at a time and stop a stale one before starting the next.

## 5. Clean up run databases

Nothing drops a run's database at its end; each run's `DROP DATABASE IF EXISTS` plus `CREATE DATABASE` makes leftovers harmless but lets them pile up. Clean them when you are done:

```sh
docker compose down -v
```

or drop the `sylament_test_%` databases on the server.
