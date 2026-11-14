<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Instructions\InstructionFiles;
use Sylament\Tests\Architecture\Rules\RuleHook;
use Symfony\Component\Yaml\Exception\ParseException;

use function dirname;

/**
 * Convention test for the agent instruction files: the root CLAUDE.md, the path-scoped rules
 * under .claude/rules/ and the skills under .claude/skills/. Every session pays for CLAUDE.md
 * and every matching read pays for a rule, so their size, scope and content are held here;
 * the loading behaviour the glob helpers mirror is recorded in docs/internals/agent-instructions.md.
 */

it('matches globs the way Claude Code does', function (string $glob, string $path, bool $matches): void {
    expect(RuleHook::globMatches($glob, $path))->toBe($matches);
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
    expect(InstructionFiles::globIsAnchored($glob))->toBe($anchored);
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
    expect(InstructionFiles::historyMarkers($content))->toBe($markers);
})->with([
    'a milestone tag' => ['since M7 · 5 the table', ['M7 · 5']],
    'a compact tag' => ['the M4·6 grouping', ['M4·6']],
    'an ISO date' => ['granted 2026-08-10', ['2026-08-10']],
    'a commit hash' => ['landed at bedd38c2', ['bedd38c2']],
    'a plain word of hex letters' => ['a decade of facade', []],
    'a version number' => ['MySQL 8.4 and pcov@8.5', []],
]);

it('keeps the root CLAUDE.md within its budget', function (): void {
    $size = InstructionFiles::fileBytes('CLAUDE.md');

    expect($size)->toBeLessThanOrEqual(12 * 1024, "CLAUDE.md is {$size} bytes; every session and subagent loads it, so it holds only what every task needs (12 KB budget). Move file-specific rules to .claude/rules/ and reasoning to docs/.");
});

it('tracks no CLAUDE.md below the root', function (): void {
    $nested = array_values(array_filter(
        InstructionFiles::trackedFiles(),
        static fn (string $file): bool => 'CLAUDE.md' !== $file && 'CLAUDE.md' === basename($file),
    ));

    expect($nested)->toBe([], 'A nested CLAUDE.md loads whenever a file beside or under it is read: ' . implode(', ', $nested));
});

it('scopes and describes every rule file', function (): void {
    $violations = [];

    foreach (InstructionFiles::ruleFiles() as $file) {
        $content = InstructionFiles::fileContent($file);

        try {
            $frontmatter = InstructionFiles::frontmatter($content);
        } catch (ParseException $exception) {
            $violations[] = "{$file}: frontmatter is not valid YAML, so Claude Code would load it as if it had no paths: " . $exception->getMessage();

            continue;
        }

        $description = $frontmatter['description'] ?? null;
        $paths = $frontmatter['paths'] ?? null;

        if (!is_string($description) || '' === mb_trim($description) || str_contains(mb_trim($description), "\n")) {
            $violations[] = "{$file}: description must be one non-empty line";
        }

        if (!is_array($paths) || [] === $paths) {
            $violations[] = "{$file}: paths must be a non-empty list — a rule without paths loads in every session";

            continue;
        }

        foreach ($paths as $glob) {
            if (!is_string($glob) || !InstructionFiles::globIsAnchored($glob)) {
                $violations[] = "{$file}: glob " . var_export($glob, true) . ' is not anchored — start it with packages/… or a leading /';

                continue;
            }

            $matched = array_filter(InstructionFiles::trackedFiles(), static fn (string $path): bool => RuleHook::globMatches($glob, $path));

            if ([] === $matched) {
                $violations[] = "{$file}: glob {$glob} matches no tracked file";
            }
        }
    }

    expect($violations)->toBe([], implode("\n", $violations));
});

it('names, describes and sizes every skill', function (): void {
    $violations = [];

    foreach (InstructionFiles::skillFiles() as $file) {
        $content = InstructionFiles::fileContent($file);

        try {
            $frontmatter = InstructionFiles::frontmatter($content);
        } catch (ParseException $exception) {
            $violations[] = "{$file}: frontmatter is not valid YAML, so Claude Code would load it as if it had no paths: " . $exception->getMessage();

            continue;
        }

        $directory = basename(dirname($file));
        $description = $frontmatter['description'] ?? null;

        if (InstructionFiles::fileBytes($file) > 16 * 1024) {
            $violations[] = "{$file}: " . InstructionFiles::fileBytes($file) . ' bytes, past the 16 KB alarm';
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

    foreach (['CLAUDE.md', ...InstructionFiles::ruleFiles(), ...InstructionFiles::skillFiles()] as $file) {
        $markers = InstructionFiles::historyMarkers(InstructionFiles::fileContent($file));

        if ([] !== $markers) {
            $violations[] = "{$file}: " . implode(', ', $markers);
        }
    }

    expect($violations)->toBe([], "Instruction files carry rules, never history — move these to docs/:\n" . implode("\n", $violations));
});

it('lists every rule file in the CLAUDE.md index with its paths and description', function (): void {
    $claude = InstructionFiles::fileContent('CLAUDE.md');
    $section = (string) preg_replace('/\A.*?^## Rule files$(.*?)(?=^## |\z).*\z/ms', '$1', $claude);
    $violations = [];

    foreach (InstructionFiles::ruleFiles() as $file) {
        try {
            $frontmatter = InstructionFiles::frontmatter(InstructionFiles::fileContent($file));
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
        if (!is_file(InstructionFiles::repositoryRoot() . '/.claude/rules/' . $name)) {
            $violations[] = "index row for a rule that does not exist: {$name}";
        }
    }

    expect($violations)->toBe([], implode("\n", $violations));
});
