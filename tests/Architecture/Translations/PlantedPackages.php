<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Translations;

use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A package tree a translation gate's own case plants under the process's writable directory, so the case proves the
 * gate reports a shape without the repository having to ship one.
 */
final class PlantedPackages
{
    /**
     * Writes each file, and a manifest for each package that has none, requiring the `sylament/*` packages named.
     *
     * @param array<string, string>       $files    contents by path, relative to the tree's root
     * @param array<string, list<string>> $requires the package directory names each package requires, by package
     *
     * @return string the tree's root, a fresh directory
     */
    public static function plant(array $files, array $requires = []): string
    {
        $directory = WorkerDirectory::path() . '/' . uniqid('translations-', true);
        $filesystem = new Filesystem();
        $packages = array_unique([...array_keys($requires), ...array_map(static fn (string $path): string => explode('/', $path)[0], array_keys($files))]);

        foreach ($packages as $package) {
            $require = array_fill_keys(array_map(static fn (string $required): string => 'sylament/' . $required, $requires[$package] ?? []), 'self.version');
            $filesystem->dumpFile($directory . '/' . $package . '/composer.json', json_encode(['name' => 'sylament/' . $package, 'require' => (object) $require], JSON_THROW_ON_ERROR));
        }

        foreach ($files as $path => $contents) {
            $filesystem->dumpFile($directory . '/' . $path, $contents);
        }

        return $directory;
    }

    /**
     * A YAML catalogue holding each key, flat and quoted, with itself as its English.
     *
     * @param list<string> $keys
     */
    public static function catalogue(array $keys): string
    {
        return implode('', array_map(
            static fn (string $key): string => sprintf('\'%1$s\': \'%1$s\'' . "\n", str_replace("'", "''", $key)),
            $keys,
        ));
    }

    public static function remove(string $directory): void
    {
        new Filesystem()->remove($directory);
    }
}
