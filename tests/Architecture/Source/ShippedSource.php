<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Source;

use Symfony\Component\Finder\Finder;

/**
 * Reads every package's shipped PHP under src/ for the scans that hold it to a rule no tool checks.
 */
final class ShippedSource
{
    /**
     * @return list<string> every shipped PHP source file, relative to the packages/ dir
     */
    public static function files(): array
    {
        $packagesDirectory = dirname(__DIR__, 3) . '/packages';

        $files = [];

        foreach (glob($packagesDirectory . '/*/src') ?: [] as $sourceDirectory) {
            foreach (Finder::create()->files()->in($sourceDirectory)->name('*.php') as $file) {
                $files[] = str_replace($packagesDirectory . '/', '', $file->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Whether the source constructs an `AssociationReader`, by its short or its qualified name.
     */
    public static function constructsAssociationReader(string $source): bool
    {
        return preg_match('/\bnew\s+\\\\?(?:\w+\\\\)*AssociationReader\s*\(/', $source) === 1;
    }

    /**
     * Whether the source puts either DBAL parameter-type class anywhere but a constant read, its own import or a
     * negative `instanceof` guard.
     */
    public static function declaresDbalParameterType(string $source): bool
    {
        $allowed = preg_replace(
            [
                '/^\s*use\s+Doctrine\\\\DBAL\\\\(?:Array)?ParameterType;\s*$/m',
                '/\b(?:Array)?ParameterType::/',
                '/instanceof\s+(?:Array)?ParameterType\b/',
            ],
            '',
            $source,
        ) ?? $source;

        return preg_match('/\b(?:Array)?ParameterType\b/', $allowed) === 1;
    }
}
