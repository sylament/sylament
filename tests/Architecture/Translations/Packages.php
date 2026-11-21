<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Translations;

use LogicException;

/**
 * The package tree the translation gates read: the repository's packages/, or a planted tree in their own cases.
 */
final class Packages
{
    public const string DIRECTORY = __DIR__ . '/../../../packages';

    /**
     * @return list<string> every package directory name holding a composer.json, sorted
     */
    public static function names(string $packagesDirectory = self::DIRECTORY): array
    {
        $names = array_map(static fn (string $manifest): string => basename(dirname($manifest)), glob($packagesDirectory . '/*/composer.json') ?: []);
        sort($names, SORT_STRING);

        return $names;
    }

    /**
     * The domain a package's catalogue holds: Filament's Laravel namespace renamed, so support's is `sylament`.
     */
    public static function domain(string $package): string
    {
        return $package === 'support' ? 'sylament' : 'sylament-' . $package;
    }

    /**
     * @return list<string> the package directory names of the `sylament/*` packages the manifest requires
     */
    public static function requires(string $package, string $packagesDirectory = self::DIRECTORY): array
    {
        $path = $packagesDirectory . '/' . $package . '/composer.json';
        $manifest = json_decode((string) file_get_contents($path), associative: true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($manifest)) {
            throw new LogicException(sprintf('%s holds no JSON object.', $path));
        }

        $require = is_array($manifest['require'] ?? null) ? $manifest['require'] : [];
        $packages = [];

        foreach (array_keys($require) as $name) {
            if (is_string($name) && str_starts_with($name, 'sylament/')) {
                $packages[] = mb_substr($name, mb_strlen('sylament/'));
            }
        }

        sort($packages, SORT_STRING);

        return $packages;
    }

    /**
     * @return list<string> the package directory names whose manifest requires the package
     */
    public static function dependents(string $package, string $packagesDirectory = self::DIRECTORY): array
    {
        return array_values(array_filter(
            self::names($packagesDirectory),
            static fn (string $candidate): bool => in_array($package, self::requires($candidate, $packagesDirectory), true),
        ));
    }
}
