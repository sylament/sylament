<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\PHPUnit;

use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;

/**
 * Publishes the root suite's connection URL to the kernel, before any test is
 * collected and in every parallel worker, and tells the database where each test
 * starts, on every platform.
 */
final class TestDatabaseExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $_ENV[TestDatabase::URL_VARIABLE] = $_SERVER[TestDatabase::URL_VARIABLE] = TestDatabase::url();

        try {
            $facade->registerSubscriber(new TestDatabaseTestStarted());
        } catch (EventFacadeIsSealedException) {
            // Pest's parallel coordinator seals the facade before it bootstraps extensions,
            // and runs no test; left uncaught, the warning PHPUnit records fails the run.
        }
    }
}
