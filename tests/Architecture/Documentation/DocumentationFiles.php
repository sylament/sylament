<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Documentation;

use Sylament\Tests\Architecture\TrackedFiles;

/**
 * Reads the repository's markdown (CLAUDE.md, .claude/ and docs/) for the documentation scans: its links, anchors
 * and milestone labels.
 */
final class DocumentationFiles
{
    /**
     * @var array<string, list<string>> anchors by file path
     */
    private static array $anchors = [];

    public static function repositoryRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @return list<string> the tracked markdown files under CLAUDE.md, .claude/ and docs/
     */
    public static function markdownFiles(): array
    {
        return array_values(array_filter(
            TrackedFiles::in(self::repositoryRoot()),
            static fn (string $file): bool => ('CLAUDE.md' === $file || str_starts_with($file, '.claude/') || str_starts_with($file, 'docs/')) && str_ends_with($file, '.md'),
        ));
    }

    /**
     * A path git ignores on purpose, such as the local docs/superpowers/, which a clean checkout does not have.
     */
    public static function isIgnored(string $path): bool
    {
        exec('git -C ' . escapeshellarg(self::repositoryRoot()) . ' check-ignore -q ' . escapeshellarg($path), $output, $status);

        return 0 === $status;
    }

    /**
     * @return list<string> CLAUDE.md, the rules and the skills
     */
    public static function instructionFiles(): array
    {
        return array_values(array_filter(
            self::markdownFiles(),
            static fn (string $file): bool => 'CLAUDE.md' === $file || str_starts_with($file, '.claude/'),
        ));
    }

    /**
     * The content with fenced code blocks and inline code spans blanked out, so examples are not read as links.
     */
    public static function prose(string $content): string
    {
        return (string) preg_replace('/`[^`\n]*`/', '``', self::withoutFences($content));
    }

    /**
     * GitHub's heading anchor: lowercased, punctuation dropped, spaces turned into hyphens.
     */
    public static function slug(string $heading): string
    {
        $text = mb_strtolower(mb_trim($heading));
        $text = (string) preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $text);

        return (string) preg_replace('/\s/u', '-', $text);
    }

    /**
     * @return list<string> the broken relative links in the markdown file at the path under the root, each as
     *                      "<target> (<reason>)"
     */
    public static function brokenLinks(string $root, string $file): array
    {
        $broken = [];

        preg_match_all('/\]\(([^)\s]+)\)/', self::prose((string) file_get_contents($root . '/' . $file)), $matches);

        foreach ($matches[1] as $target) {
            if (1 === preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)) {
                continue;
            }

            [$path, $anchor] = array_pad(explode('#', $target, 2), 2, null);
            $resolved = '' === $path ? $root . '/' . $file : dirname($root . '/' . $file) . '/' . rawurldecode((string) $path);

            if (!file_exists($resolved)) {
                $broken[] = "{$target} (no such file)";

                continue;
            }

            if (null !== $anchor && '' !== $anchor && str_ends_with($resolved, '.md') && !in_array(rawurldecode($anchor), self::anchors($resolved), true)) {
                $broken[] = "{$target} (no such heading)";
            }
        }

        return $broken;
    }

    /**
     * @return list<string> the milestone labels in the content's prose, such as `M3` or `M7 · 5`
     */
    public static function milestoneLabels(string $content): array
    {
        preg_match_all('/\bM\d+(?:\s?·\s?\d+[a-z]?)?\b/u', self::prose($content), $matches);

        return array_values(array_unique($matches[0]));
    }

    private static function withoutFences(string $content): string
    {
        return (string) preg_replace('/^(`{3,}|~{3,}).*?^\1/ms', '', $content);
    }

    /**
     * @return list<string> every anchor the file's headings produce, with GitHub's -1, -2 suffixes for repeats
     */
    private static function anchors(string $file): array
    {
        if (!isset(self::$anchors[$file])) {
            preg_match_all('/^#{1,6}\s+(.+?)\s*#*$/m', self::withoutFences((string) file_get_contents($file)), $matches);
            $anchors = [];
            $seen = [];

            foreach ($matches[1] as $heading) {
                $slug = self::slug($heading);
                $anchors[] = isset($seen[$slug]) ? $slug . '-' . $seen[$slug] : $slug;
                $seen[$slug] = ($seen[$slug] ?? 0) + 1;
            }

            self::$anchors[$file] = $anchors;
        }

        return self::$anchors[$file];
    }
}
