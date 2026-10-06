<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function dirname;

/**
 * Convention test for the agent instruction files: the root CLAUDE.md, the path-scoped rules
 * under .claude/rules/ and the skills under .claude/skills/. Every session pays for CLAUDE.md
 * and every matching read pays for a rule, so their size, scope and content are held here;
 * the loading behaviour the glob helpers mirror is recorded in docs/internals/agent-instructions.md.
 */
function instructionRepositoryRoot(): string
{
    return dirname(__DIR__, 4);
}

/**
 * @return list<string> every tracked file, relative to the repository root
 */
function instructionTrackedFiles(): array
{
    static $files = null;

    if (null === $files) {
        exec('git -C ' . escapeshellarg(instructionRepositoryRoot()) . ' ls-files', $output, $status);

        if (0 !== $status) {
            throw new RuntimeException('git ls-files failed; the instruction-file check reads tracked files through git.');
        }

        $files = $output;
    }

    return $files;
}

/**
 * @return list<string> every rule file under .claude/rules/, relative to the repository root
 */
function instructionRuleFiles(): array
{
    $directory = instructionRepositoryRoot() . '/.claude/rules';

    if (!is_dir($directory)) {
        return [];
    }

    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && str_ends_with((string) $file->getFilename(), '.md')) {
            $files[] = str_replace(instructionRepositoryRoot() . '/', '', $file->getPathname());
        }
    }

    sort($files);

    return $files;
}

/**
 * @return list<string> every skill file, relative to the repository root
 */
function instructionSkillFiles(): array
{
    $files = array_map(
        static fn (string $path): string => str_replace(instructionRepositoryRoot() . '/', '', $path),
        glob(instructionRepositoryRoot() . '/.claude/skills/*/SKILL.md') ?: [],
    );

    sort($files);

    return $files;
}

/**
 * @return array<string, mixed> the YAML frontmatter, or an empty array when the file has none
 */
function instructionFrontmatter(string $content): array
{
    if (1 !== preg_match('/\A---\n(.*?)\n---\n/s', $content, $match)) {
        return [];
    }

    $parsed = Yaml::parse($match[1]);

    return is_array($parsed) ? $parsed : [];
}

function instructionGlobIsAnchored(string $glob): bool
{
    if (str_starts_with($glob, '/')) {
        return true;
    }

    return !str_starts_with($glob, '**') && str_contains((string) preg_replace('#/\*\*$#', '', $glob), '/');
}

function instructionGlobMatches(string $glob, string $path): bool
{
    return 1 === preg_match(Glob::toRegex(mb_ltrim($glob, '/'), strictLeadingDot: false), $path);
}

/**
 * @return list<string> the milestone tags, ISO dates and hash-like tokens in the content
 */
function instructionHistoryMarkers(string $content): array
{
    preg_match_all(
        '/\bM\d+(?:\s?·\s?\d+[a-z]?)?\b|\b20\d\d\-\d\d\-\d\d\b|\b(?=[0-9a-f]*\d)(?=[0-9a-f]*[a-f])[0-9a-f]{7,40}\b/u',
        $content,
        $matches,
    );

    return array_values(array_unique($matches[0]));
}

function instructionFileBytes(string $file): int
{
    return (int) filesize(instructionRepositoryRoot() . '/' . $file);
}

function instructionFileContent(string $file): string
{
    return (string) file_get_contents(instructionRepositoryRoot() . '/' . $file);
}

it('matches globs the way Claude Code does', function (string $glob, string $path, bool $matches): void {
    expect(instructionGlobMatches($glob, $path))->toBe($matches);
})->with([
    'a nested package source file' => ['packages/*/src/**/*.php', 'packages/forms/src/Components/TextInput.php', true],
    'a file directly in src' => ['packages/*/src/**/*.php', 'packages/forms/src/helpers.php', true],
    'the same shape under vendor' => ['packages/*/src/**/*.php', 'vendor/sylament/x/packages/forms/src/X.php', false],
    'a brace member' => ['packages/{actions,forms}/tests/**', 'packages/forms/tests/Unit/XTest.php', true],
    'outside the brace set' => ['packages/{actions,forms}/tests/**', 'packages/tables/tests/Unit/XTest.php', false],
    'a brace extension' => ['packages/*/assets/**/*.{ts,mjs,json}', 'packages/forms/assets/src/controllers/select_controller.ts', true],
    'a root file' => ['/composer.json', 'composer.json', true],
    'a nested file of the same name' => ['/composer.json', 'packages/forms/composer.json', false],
    'a dot directory' => ['/.claude/**', '.claude/rules/php.md', true],
]);

it('tells an anchored glob from one Claude Code matches at any depth', function (string $glob, bool $anchored): void {
    expect(instructionGlobIsAnchored($glob))->toBe($anchored);
})->with([
    'a path with a middle slash' => ['packages/*/src/**/*.php', true],
    'a path ending in /**' => ['packages/*/tests/**', true],
    'a root file' => ['/composer.json', true],
    'a root directory' => ['/docs/**', true],
    'a single segment plus /**' => ['src/**', false],
    'a bare name' => ['composer.json', false],
    'a leading **' => ['**/*.php', false],
]);

it('finds the history markers instruction files must not carry', function (string $content, array $markers): void {
    expect(instructionHistoryMarkers($content))->toBe($markers);
})->with([
    'a milestone tag' => ['since M7 · 5 the table', ['M7 · 5']],
    'a compact tag' => ['the M4·6 grouping', ['M4·6']],
    'an ISO date' => ['granted 2026-08-10', ['2026-08-10']],
    'a commit hash' => ['landed at bedd38c2', ['bedd38c2']],
    'a plain word of hex letters' => ['a decade of facade', []],
    'a version number' => ['MySQL 8.4 and pcov@8.5', []],
]);

it('keeps the root CLAUDE.md within its budget', function (): void {
    $size = instructionFileBytes('CLAUDE.md');

    expect($size)->toBeLessThanOrEqual(12 * 1024, "CLAUDE.md is {$size} bytes; every session and subagent loads it, so it holds only what every task needs (12 KB budget). Move file-specific rules to .claude/rules/ and reasoning to docs/.");
});

it('tracks no CLAUDE.md below the root', function (): void {
    $nested = array_values(array_filter(
        instructionTrackedFiles(),
        static fn (string $file): bool => 'CLAUDE.md' !== $file && 'CLAUDE.md' === basename($file),
    ));

    expect($nested)->toBe([], 'A nested CLAUDE.md loads whenever a file beside or under it is read: ' . implode(', ', $nested));
});

it('scopes, describes and sizes every rule file', function (): void {
    $violations = [];

    foreach (instructionRuleFiles() as $file) {
        $content = instructionFileContent($file);

        try {
            $frontmatter = instructionFrontmatter($content);
        } catch (ParseException $exception) {
            $violations[] = "{$file}: frontmatter is not valid YAML, so Claude Code would load it as if it had no paths: " . $exception->getMessage();

            continue;
        }

        $description = $frontmatter['description'] ?? null;
        $paths = $frontmatter['paths'] ?? null;

        if (instructionFileBytes($file) > 8 * 1024) {
            $violations[] = "{$file}: " . instructionFileBytes($file) . ' bytes, over the 8 KB budget — split it by paths or move background to docs/';
        }

        if (!is_string($description) || '' === mb_trim($description) || str_contains(mb_trim($description), "\n")) {
            $violations[] = "{$file}: description must be one non-empty line";
        }

        if (!is_array($paths) || [] === $paths) {
            $violations[] = "{$file}: paths must be a non-empty list — a rule without paths loads in every session";

            continue;
        }

        foreach ($paths as $glob) {
            if (!is_string($glob) || !instructionGlobIsAnchored($glob)) {
                $violations[] = "{$file}: glob " . var_export($glob, true) . ' is not anchored — start it with packages/… or a leading /';

                continue;
            }

            $matched = array_filter(instructionTrackedFiles(), static fn (string $path): bool => instructionGlobMatches($glob, $path));

            if ([] === $matched) {
                $violations[] = "{$file}: glob {$glob} matches no tracked file";
            }
        }
    }

    expect($violations)->toBe([], implode("\n", $violations));
});

it('names, describes and sizes every skill', function (): void {
    $violations = [];

    foreach (instructionSkillFiles() as $file) {
        $content = instructionFileContent($file);

        try {
            $frontmatter = instructionFrontmatter($content);
        } catch (ParseException $exception) {
            $violations[] = "{$file}: frontmatter is not valid YAML, so Claude Code would load it as if it had no paths: " . $exception->getMessage();

            continue;
        }

        $directory = basename(dirname($file));
        $description = $frontmatter['description'] ?? null;

        if (instructionFileBytes($file) > 12 * 1024) {
            $violations[] = "{$file}: " . instructionFileBytes($file) . ' bytes, over the 12 KB budget';
        }

        if (($frontmatter['name'] ?? null) !== $directory) {
            $violations[] = "{$file}: name must equal its directory, {$directory}";
        }

        if (!is_string($description) || '' === mb_trim($description) || str_contains(mb_trim($description), "\n")) {
            $violations[] = "{$file}: description must be one non-empty line";
        }
    }

    expect($violations)->toBe([], implode("\n", $violations));
});

it('keeps history out of every instruction file', function (): void {
    $violations = [];

    foreach (['CLAUDE.md', ...instructionRuleFiles(), ...instructionSkillFiles()] as $file) {
        $markers = instructionHistoryMarkers(instructionFileContent($file));

        if ([] !== $markers) {
            $violations[] = "{$file}: " . implode(', ', $markers);
        }
    }

    expect($violations)->toBe([], "Instruction files carry rules, never history — move these to docs/:\n" . implode("\n", $violations));
});

it('lists every rule file in the CLAUDE.md index with its paths and description', function (): void {
    $claude = instructionFileContent('CLAUDE.md');
    $section = (string) preg_replace('/\A.*?^## Rule files$(.*?)(?=^## |\z).*\z/ms', '$1', $claude);
    $violations = [];

    foreach (instructionRuleFiles() as $file) {
        try {
            $frontmatter = instructionFrontmatter(instructionFileContent($file));
        } catch (ParseException) {
            continue;
        }

        $paths = is_array($frontmatter['paths'] ?? null) ? $frontmatter['paths'] : [];
        $globs = implode(', ', array_map(static fn (mixed $glob): string => '`' . (is_string($glob) ? $glob : '') . '`', $paths));
        $description = is_string($frontmatter['description'] ?? null) ? $frontmatter['description'] : '';
        $row = '| `' . mb_substr($file, mb_strlen('.claude/rules/')) . "` | {$globs} | {$description} |";

        if (!str_contains($section, $row)) {
            $violations[] = "missing or stale index row: {$row}";
        }
    }

    preg_match_all('/^\| `([^`]+\.md)` \|/m', $section, $rows);

    foreach ($rows[1] as $name) {
        if (!is_file(instructionRepositoryRoot() . '/.claude/rules/' . $name)) {
            $violations[] = "index row for a rule that does not exist: {$name}";
        }
    }

    expect($violations)->toBe([], implode("\n", $violations));
});
