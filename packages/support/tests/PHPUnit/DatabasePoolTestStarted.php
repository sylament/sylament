<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\PHPUnit;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Sylament\Support\Tests\Doctrine\DatabasePool;

final class DatabasePoolTestStarted implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        DatabasePool::instance()->testStarted();
    }
}
