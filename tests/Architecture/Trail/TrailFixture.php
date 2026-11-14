<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Trail;

use Sylament\Tests\Infrastructure\WorkerDirectory;

/**
 * Builds the port-trail trees the trail's own cases run against.
 */
final class TrailFixture
{
    public static function repositoryRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * A docs/progress path for a fixture, assembled so that the reference check reading this file finds no
     * reference to a trail file that does not exist.
     */
    public static function docs(string $relative): string
    {
        return 'docs/progress/' . $relative;
    }

    /**
     * @param array<string, string> $files content keyed by path; a path ending in / is an empty directory
     */
    public static function tree(array $files): string
    {
        $root = WorkerDirectory::path() . '/' . uniqid('trail-', true);

        foreach ($files as $path => $content) {
            $directory = str_ends_with($path, '/') ? $root . '/' . $path : dirname($root . '/' . $path);

            if (!is_dir($directory)) {
                mkdir($directory, recursive: true);
            }

            if (!str_ends_with($path, '/')) {
                file_put_contents($root . '/' . $path, $content);
            }
        }

        return $root;
    }

    /**
     * @param list<string> $ports
     * @param list<string> $owns
     */
    public static function frontmatter(string $description, array $ports, array $owns): string
    {
        return "---\ndescription: {$description}\nports:" . self::yamlList($ports) . "\nowns:" . self::yamlList($owns) . "\n---\n";
    }

    /**
     * @param list<string> $rows each "| … |" line under the five-column header
     */
    public static function surface(array $rows): string
    {
        return "## Surface\n\n| Feature | Ports | Status | Blocked by | Detail |\n| --- | --- | --- | --- | --- |\n" . implode("\n", $rows) . "\n";
    }

    /**
     * Two packages and three detail files that pass every check once the indexes are written.
     *
     * @return array<string, string>
     */
    public static function greenTree(): array
    {
        $readme = static fn (string $title, string $unported): string => "# {$title} — port trail\n\nPorts it.\n\n## Files\n\n" . Trail::INDEX_START . "\n" . Trail::INDEX_END . "\n\n## Unported\n\n| Filament | Status | Blocked by | Detail |\n| --- | --- | --- | --- |\n{$unported}";

        return [
            'packages/alpha/src/Widget.php' => "<?php\n// The engine question is in " . self::docs('alpha/widget.md') . ', section "Widget waits on the engine".' . "\n",
            'packages/alpha/src/Concerns/HasThing.php' => "<?php\n",
            'packages/alpha/templates/widget.html.twig' => "<div></div>\n",
            'packages/alpha/tests/WidgetTest.php' => "<?php\n",
            'packages/panels/src/Panel.php' => "<?php\n",
            'packages/panels/assets/src/panel.ts' => "export {};\n",
            'vendor/filament/alpha/src/Widget.php' => "<?php\n",
            'vendor/filament/alpha/src/Gadget.php' => "<?php\n",
            'vendor/filament/alpha/src/Concerns/HasThing.php' => "<?php\n",
            'vendor/filament/filament/src/Panel.php' => "<?php\n",
            'docs/internals/notes.md' => "# Notes\n\nSee `" . self::docs('panels/panel.md') . "` (\"The panel waits on\nrouting\").\n",
            self::docs('README.md') => "# Progress\n\n## Packages\n\n" . Trail::INDEX_START . "\nstale\n" . Trail::INDEX_END . "\n\n## Statuses\n",
            self::docs('alpha/README.md') => $readme('Alpha', "| [`Gadget.php`](../../../vendor/filament/alpha/src/Gadget.php) | todo | — | — |\n"),
            self::docs('panels/README.md') => $readme('Panels', ''),
            self::docs('alpha/widget.md') => self::frontmatter('The widget; its concerns are in has-thing.md.', ['vendor/filament/alpha/src/Widget.php'], ['packages/alpha/src/', 'packages/alpha/templates/widget.html.twig'])
                . "# Widget (alpha)\n\n" . self::surface([
                    '| `make()` | `Widget.php` | done | — | — |',
                    '| `render()` | `Widget.php` | in progress | — | [detail](#widget-waits-on-the-engine) |',
                    '| `mask()` | `Widget.php` | todo | — | — |',
                ]) . "\n## Details\n\n### `Widget` waits on the engine\n\nOne sentence.\n",
            self::docs('alpha/has-thing.md') => self::frontmatter('The HasThing concern.', ['vendor/filament/alpha/src/Concerns/'], ['packages/alpha/src/Concerns/'])
                . "# HasThing (alpha)\n\n" . self::surface([
                    '| `thing()` | `HasThing.php` | done | — | [semantics](#thing-is-subtle) |',
                    '| `legacyThing()` | `HasThing.php` | skipped | — | — |',
                ]) . "\n## Semantics\n\n### Thing is subtle\n\nOne sentence.\n",
            self::docs('panels/panel.md') => self::frontmatter('The panel | its assets.', ['vendor/filament/filament/src/Panel.php'], ['packages/panels/src/Panel.php', 'packages/panels/assets/src/'])
                . "# Panel (panels)\n\n" . self::surface([
                    '| `route()` | `Panel.php` | blocked | routing | [detail](#the-panel-waits-on-routing) |',
                    '| _rest not audited yet_ | — | — | — | — |',
                ]) . "\n## Details\n\n### The panel waits on routing\n\nOne sentence.\n",
        ];
    }

    /**
     * @param array<string, string> $files
     *
     * @return list<string> the fixture's tracked files: everything except vendor/
     */
    public static function tracked(array $files): array
    {
        return array_values(array_filter(array_keys($files), static fn (string $path): bool => !str_starts_with($path, 'vendor/') && !str_ends_with($path, '/')));
    }

    /**
     * @param list<string> $entries
     */
    private static function yamlList(array $entries): string
    {
        return [] === $entries ? ' []' : implode('', array_map(static fn (string $entry): string => "\n  - {$entry}", $entries));
    }
}
