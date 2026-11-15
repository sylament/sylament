<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure;

use LogicException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The writable root of one test process, under `TEST_TOKEN`, which only a parallel run sets,
 * or 0. A run that sets `SYLAMENT_TEST_RUN` gets a directory of its own above it, so two
 * runs in one checkout never share one.
 */
final class WorkerDirectory
{
    public const string RUN_VARIABLE = 'SYLAMENT_TEST_RUN';

    public static function path(): string
    {
        $run = self::run();

        return dirname(__DIR__, 2) . '/var/tests/' . ($run === null ? '' : $run . '/') . (getenv('TEST_TOKEN') ?: '0');
    }

    /**
     * The run `SYLAMENT_TEST_RUN` names, or null when it is unset or empty.
     */
    public static function run(): ?string
    {
        $run = getenv(self::RUN_VARIABLE);

        if ($run === false || $run === '') {
            return null;
        }

        if (preg_match('/^[a-z0-9_]{1,16}$/', $run) !== 1) {
            throw new LogicException(sprintf('%s is "%s"; it must be 1 to 16 characters of a-z, 0-9 and _, as in lane_a.', self::RUN_VARIABLE, $run));
        }

        return $run;
    }

    /**
     * Locks the directory until the process ends, through a handle of its own, so a second
     * process given the same directory fails here instead of corrupting this one.
     */
    public static function claim(): void
    {
        $directory = self::path();
        new Filesystem()->mkdir($directory);

        $handle = fopen($directory . '/.lock', 'c') ?: throw new RuntimeException(sprintf('Could not open %s/.lock.', $directory));

        if (!flock($handle, LOCK_EX | LOCK_NB, $wouldBlock)) {
            fclose($handle);

            throw new RuntimeException($wouldBlock === 1
                ? sprintf('Another test process holds %s. Give each concurrent run in one checkout its own %s, as in `%2$s=lane_a vendor/bin/pest`.', $directory, self::RUN_VARIABLE)
                : sprintf('Could not lock %s/.lock.', $directory));
        }

        register_shutdown_function(static function () use ($handle): void {
            flock($handle, LOCK_UN);
            fclose($handle);
        });
    }
}
