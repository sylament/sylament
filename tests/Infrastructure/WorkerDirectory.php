<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure;

/**
 * The writable root of one test process: Paratest numbers its workers through `TEST_TOKEN`,
 * and a serial run is worker 0.
 */
final class WorkerDirectory
{
    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/var/tests/' . (getenv('TEST_TOKEN') ?: '0');
    }
}
