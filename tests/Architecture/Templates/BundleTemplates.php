<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Templates;

use Symfony\Component\Finder\Finder;

/**
 * Reads the bundles' Twig templates for the scans of their layout and of what they call on their host.
 */
final class BundleTemplates
{
    /**
     * @return list<string> paths under the named half of every bundle's templates/, relative to the packages/ dir
     */
    public static function in(string $half): array
    {
        $packagesDirectory = dirname(__DIR__, 3) . '/packages';

        $files = [];

        foreach (glob($packagesDirectory . '/*/templates/' . $half) ?: [] as $directory) {
            foreach (Finder::create()->files()->in($directory)->name('*.twig') as $file) {
                $files[] = str_replace($packagesDirectory . '/', '', $file->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    /**
     * A tables template reaches its host through `component.getLiveComponent`, which is typed `InteractsWithTable`.
     *
     * @return list<array{string, string}> template path (relative to packages/) and host method name
     */
    public static function tablesHostCalls(): array
    {
        $packagesDirectory = dirname(__DIR__, 3) . '/packages';

        $calls = [];

        foreach (glob($packagesDirectory . '/tables/templates/*/*/*.html.twig') ?: [] as $template) {
            preg_match_all('/getLiveComponent\.(\w+)/', (string) file_get_contents($template), $matches);

            foreach (array_unique($matches[1]) as $method) {
                $calls[] = [str_replace($packagesDirectory . '/', '', $template), $method];
            }
        }

        return $calls;
    }
}
