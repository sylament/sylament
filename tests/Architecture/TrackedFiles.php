<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use RuntimeException;

/**
 * The files git tracks in a repository: what the scans, the rule hook and the trail read, so an ignored or
 * untracked file never counts.
 */
final class TrackedFiles
{
    /**
     * @return list<string> every tracked file, relative to the repository root
     */
    public static function in(string $root): array
    {
        exec('git -C ' . escapeshellarg($root) . ' ls-files', $output, $status);

        if (0 !== $status) {
            throw new RuntimeException("git ls-files failed in {$root}; the scans, the rule hook and the trail read tracked files through git.");
        }

        return $output;
    }
}
