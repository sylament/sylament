<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Instructions;

use Sylament\Tests\Architecture\TrackedFiles;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads the agent instruction files (the root CLAUDE.md, the rules under .claude/rules/ and the skills under
 * .claude/skills/) for the scans of their size, scope and content; the loading behaviour the glob helpers mirror is
 * recorded in docs/internals/agent-instructions.md.
 */
final class InstructionFiles
{
    /**
     * @var list<string>|null
     */
    private static ?array $trackedFiles = null;

    public static function repositoryRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @return list<string> every tracked file, relative to the repository root
     */
    public static function trackedFiles(): array
    {
        return self::$trackedFiles ??= TrackedFiles::in(self::repositoryRoot());
    }

    /**
     * @return list<string> every rule file under .claude/rules/, relative to the repository root
     */
    public static function ruleFiles(): array
    {
        $directory = self::repositoryRoot() . '/.claude/rules';

        if (!is_dir($directory)) {
            return [];
        }

        $files = [];

        foreach (Finder::create()->files()->in($directory)->name('*.md') as $file) {
            $files[] = str_replace(self::repositoryRoot() . '/', '', $file->getPathname());
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string> every skill file, relative to the repository root
     */
    public static function skillFiles(): array
    {
        $files = array_map(
            static fn (string $path): string => str_replace(self::repositoryRoot() . '/', '', $path),
            glob(self::repositoryRoot() . '/.claude/skills/*/SKILL.md') ?: [],
        );

        sort($files);

        return $files;
    }

    /**
     * @return array<mixed> the YAML frontmatter, or an empty array when the file has none
     */
    public static function frontmatter(string $content): array
    {
        if (1 !== preg_match('/\A---\n(.*?)\n---\n/s', $content, $match)) {
            return [];
        }

        $parsed = Yaml::parse($match[1]);

        return is_array($parsed) ? $parsed : [];
    }

    public static function globIsAnchored(string $glob): bool
    {
        if (str_starts_with($glob, '/')) {
            return true;
        }

        return !str_starts_with($glob, '**') && str_contains((string) preg_replace('#/\*\*$#', '', $glob), '/');
    }

    /**
     * @return list<string> the milestone tags, ISO dates and hash-like tokens in the content
     */
    public static function historyMarkers(string $content): array
    {
        preg_match_all(
            '/\bM\d+(?:\s?·\s?\d+[a-z]?)?\b|\b20\d\d\-\d\d\-\d\d\b|\b(?=[0-9a-f]*\d)(?=[0-9a-f]*[a-f])[0-9a-f]{7,40}\b/u',
            $content,
            $matches,
        );

        return array_values(array_unique($matches[0]));
    }

    /**
     * @return list<string> each rule the index section lists that has no file under .claude/rules/
     */
    public static function indexRowsWithoutRule(string $section): array
    {
        preg_match_all('/^\| `([^`]+\.md)` \|/m', $section, $rows);

        return array_values(array_filter($rows[1], static fn (string $name): bool => !is_file(self::repositoryRoot() . '/.claude/rules/' . $name)));
    }

    public static function fileBytes(string $file): int
    {
        return (int) filesize(self::repositoryRoot() . '/' . $file);
    }

    public static function fileContent(string $file): string
    {
        return (string) file_get_contents(self::repositoryRoot() . '/' . $file);
    }
}
