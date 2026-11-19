<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\PHPUnit;

use LogicException;
use Pest\Plugins\Parallel;
use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Throwable;

/**
 * Publishes the root suite's connection URL to the kernel before any test is
 * collected, claims the process's directory, and tells the database where each
 * test starts, on every platform and in every parallel worker. Pest's parallel
 * coordinator claims the run's directory and runs no test.
 */
final class TestDatabaseExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        try {
            $this->start($facade);
        } catch (Throwable $throwable) {
            // PHPUnit turns an exception thrown here into a warning and runs every test anyway,
            // in a directory and database this process may not own.
            fwrite(STDERR, sprintf('%s: %s%s', $throwable::class, $throwable->getMessage(), PHP_EOL));

            exit(1);
        }
    }

    private function start(Facade $facade): void
    {
        if (Parallel::isWorker() && WorkerDirectory::worker() === null) {
            throw new LogicException(sprintf("A parallel worker needs ParaTest's %s to own a directory and a database: drop --no-test-tokens.", WorkerDirectory::TOKEN_VARIABLE));
        }

        $_ENV[TestDatabase::URL_VARIABLE] = $_SERVER[TestDatabase::URL_VARIABLE] = TestDatabase::url();

        WorkerDirectory::claim();

        try {
            $facade->registerSubscriber(new TestDatabaseTestStarted());
        } catch (EventFacadeIsSealedException $eventFacadeIsSealedException) {
            // Pest's coordinator seals the facade before it bootstraps extensions; a `--parallel`
            // run Pest falls back to running in series is no coordinator and registers.
            if (!Parallel::isEnabled() || Parallel::isWorker()) {
                throw $eventFacadeIsSealedException;
            }
        }
    }
}
