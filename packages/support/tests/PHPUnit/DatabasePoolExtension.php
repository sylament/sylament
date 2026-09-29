<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Sylament\Support\Tests\Doctrine\TestPlatform;

/**
 * Tells the database pool where each test starts. Inert while the suites run on
 * in-memory SQLite.
 */
final class DatabasePoolExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if (TestPlatform::current() === TestPlatform::Sqlite) {
            return;
        }

        $facade->registerSubscriber(new DatabasePoolTestStarted());
    }
}
