<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\PHPUnit;

use Pest\Plugins\Parallel;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Throwable;

/**
 * Publishes the root suite's connection URL to the kernel, before any test is
 * collected and in every parallel worker, claims the worker directory in every
 * process that runs tests, and tells the database where each test starts, on
 * every platform.
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
        $_ENV[TestDatabase::URL_VARIABLE] = $_SERVER[TestDatabase::URL_VARIABLE] = TestDatabase::url();

        // Pest's parallel coordinator runs no test and bootstraps extensions after sealing
        // the event facade, where registering throws a warning that fails the run unprinted.
        if (Parallel::isEnabled() && !Parallel::isWorker()) {
            return;
        }

        WorkerDirectory::claim();

        $facade->registerSubscriber(new TestDatabaseTestStarted());
    }
}
