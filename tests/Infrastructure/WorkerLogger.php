<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Log\Logger;

/**
 * The kernel's logger. FrameworkBundle's fallback writes every error a request logs to
 * stderr, and a tester that lets the kernel catch exceptions logs each one it rethrows.
 */
final class WorkerLogger
{
    public static function create(string $directory): Logger
    {
        new Filesystem()->mkdir($directory);

        return new Logger(output: $directory . '/test.log');
    }
}
