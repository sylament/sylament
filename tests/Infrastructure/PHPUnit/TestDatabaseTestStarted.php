<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\PHPUnit;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;

final class TestDatabaseTestStarted implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        TestDatabase::testStarted();
    }
}
