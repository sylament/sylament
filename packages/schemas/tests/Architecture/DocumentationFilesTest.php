<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use RuntimeException;

use function dirname;

/**
 * Convention test for the repository's markdown: CLAUDE.md, .claude/ and docs/. It holds what a machine
 * can prove about documentation — every relative link and anchor resolves, the instruction files name
 * only paths and scripts that exist, and no port-trail detail file is orphaned from its package README.
 * Whether a sentence is still true is a judgment, which the docs-audit skill makes at a milestone's end.
 */
function documentationRepositoryRoot(): string
{
    return dirname(__DIR__, 4);
}

/**
 * @return list<string> the tracked markdown files under CLAUDE.md, .claude/ and docs/
 */
function documentationMarkdownFiles(): array
{
    exec('git -C ' . escapeshellarg(documentationRepositoryRoot()) . ' ls-files -- CLAUDE.md .claude docs', $output, $status);

    if (0 !== $status) {
        throw new RuntimeException('git ls-files failed; the documentation check reads tracked files through git.');
    }

    return array_values(array_filter($output, static fn (string $file): bool => str_ends_with($file, '.md')));
}

/**
 * A path git ignores on purpose, such as the local docs/superpowers/, which a clean checkout does not have.
 */
function documentationIsIgnored(string $path): bool
{
    exec('git -C ' . escapeshellarg(documentationRepositoryRoot()) . ' check-ignore -q ' . escapeshellarg($path), $output, $status);

    return 0 === $status;
}

/**
 * @return list<string> CLAUDE.md, the rules and the skills
 */
function documentationInstructionFiles(): array
{
    return array_values(array_filter(
        documentationMarkdownFiles(),
        static fn (string $file): bool => 'CLAUDE.md' === $file || str_starts_with($file, '.claude/'),
    ));
}

function documentationWithoutFences(string $content): string
{
    return (string) preg_replace('/^(`{3,}|~{3,}).*?^\1/ms', '', $content);
}

/**
 * The content with fenced code blocks and inline code spans blanked out, so examples are not read as links.
 */
function documentationProse(string $content): string
{
    return (string) preg_replace('/`[^`\n]*`/', '``', documentationWithoutFences($content));
}

/**
 * GitHub's heading anchor: lowercased, punctuation dropped, spaces turned into hyphens.
 */
function documentationSlug(string $heading): string
{
    $text = mb_strtolower(mb_trim($heading));
    $text = (string) preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $text);

    return (string) preg_replace('/\s/u', '-', $text);
}

/**
 * @return list<string> every anchor the file's headings produce, with GitHub's -1, -2 suffixes for repeats
 */
function documentationAnchors(string $file): array
{
    static $cache = [];

    if (!isset($cache[$file])) {
        preg_match_all('/^#{1,6}\s+(.+?)\s*#*$/m', documentationWithoutFences((string) file_get_contents($file)), $matches);
        $anchors = [];
        $seen = [];

        foreach ($matches[1] as $heading) {
            $slug = documentationSlug($heading);
            $anchors[] = isset($seen[$slug]) ? $slug . '-' . $seen[$slug] : $slug;
            $seen[$slug] = ($seen[$slug] ?? 0) + 1;
        }

        $cache[$file] = $anchors;
    }

    return $cache[$file];
}

/**
 * @return list<string> the broken relative links in a markdown file, each as "<target> (<reason>)"
 */
function documentationBrokenLinks(string $file): array
{
    $root = documentationRepositoryRoot();
    $broken = [];

    preg_match_all('/\]\(([^)\s]+)\)/', documentationProse((string) file_get_contents($root . '/' . $file)), $matches);

    foreach ($matches[1] as $target) {
        if (1 === preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)) {
            continue;
        }

        [$path, $anchor] = array_pad(explode('#', $target, 2), 2, null);
        $resolved = '' === $path ? $root . '/' . $file : dirname($root . '/' . $file) . '/' . rawurldecode($path);

        if (!file_exists($resolved)) {
            $broken[] = "{$target} (no such file)";

            continue;
        }

        if (null !== $anchor && '' !== $anchor && str_ends_with($resolved, '.md') && !in_array(rawurldecode($anchor), documentationAnchors($resolved), true)) {
            $broken[] = "{$target} (no such heading)";
        }
    }

    return $broken;
}

it('turns headings into GitHub anchors', function (string $heading, string $anchor): void {
    expect(documentationSlug($heading))->toBe($anchor);
})->with([
    'plain words' => ['The component model', 'the-component-model'],
    'code and punctuation' => ["`evaluate()`'s generic", 'evaluates-generic'],
    'a numbered heading' => ['1. Symfony idiom, not Laravel idiom', '1-symfony-idiom-not-laravel-idiom'],
    'a question' => ['How do the two attribute bags render a value?', 'how-do-the-two-attribute-bags-render-a-value'],
    'an accented letter' => ['Čo je nové', 'čo-je-nové'],
]);

it('finds a broken link and a broken anchor', function (): void {
    $file = 'docs/' . uniqid('documentation-probe-', true) . '.md';
    $path = documentationRepositoryRoot() . '/' . $file;
    file_put_contents($path, "# Probe `Code` heading\n\n[here](#probe-code-heading) [gone](no-such-file.md) [bad](principles.md#no-such-heading) [ok](principles.md) `[code](ignored.md)`\n");

    try {
        expect(documentationBrokenLinks($file))->toBe(['no-such-file.md (no such file)', 'principles.md#no-such-heading (no such heading)']);
    } finally {
        unlink($path);
    }
});

it('resolves every relative link and anchor in CLAUDE.md, .claude/ and docs/', function (): void {
    $violations = [];

    foreach (documentationMarkdownFiles() as $file) {
        foreach (documentationBrokenLinks($file) as $link) {
            $violations[] = "{$file}: {$link}";
        }
    }

    expect($violations)->toBe([], "Broken documentation links:\n" . implode("\n", $violations));
});

it('names only repository paths that exist in the instruction files', function (): void {
    $root = documentationRepositoryRoot();
    $violations = [];

    foreach (documentationInstructionFiles() as $file) {
        preg_match_all('/`((?:packages|docs|\.claude|vendor\/filament)\/[^`\s]*|CLAUDE\.md|composer\.json|package\.json|pnpm-workspace\.yaml|biome\.json|tsconfig\.base\.json)`/', (string) file_get_contents($root . '/' . $file), $matches);

        foreach (array_unique($matches[1]) as $path) {
            if (1 === preg_match('/[*{}<>$]|\.\.\./', $path) || file_exists($root . '/' . mb_rtrim($path, '/')) || documentationIsIgnored($path)) {
                continue;
            }

            $violations[] = "{$file}: `{$path}` does not exist";
        }
    }

    expect($violations)->toBe([], "Instruction files name paths that do not exist:\n" . implode("\n", $violations));
});

it('names only composer and pnpm scripts that exist in the instruction files', function (): void {
    $root = documentationRepositoryRoot();
    $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $pnpm = json_decode((string) file_get_contents($root . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
    $composerScripts = is_array($composer) && is_array($composer['scripts'] ?? null) ? array_keys($composer['scripts']) : [];
    $pnpmScripts = is_array($pnpm) && is_array($pnpm['scripts'] ?? null) ? array_keys($pnpm['scripts']) : [];
    $violations = [];

    foreach (documentationInstructionFiles() as $file) {
        $content = (string) file_get_contents($root . '/' . $file);
        preg_match_all('/`composer ((?:test|format)(?::[a-z-]+)?)\b/', $content, $composerNames);
        preg_match_all('/`pnpm ((?:test|format|build|watch|clean)(?::[a-z-]+)?)\b/', $content, $pnpmNames);

        foreach (array_unique($composerNames[1]) as $script) {
            if (!in_array($script, $composerScripts, true)) {
                $violations[] = "{$file}: `composer {$script}` is not a script in composer.json";
            }
        }

        foreach (array_unique($pnpmNames[1]) as $script) {
            if (!in_array($script, $pnpmScripts, true)) {
                $violations[] = "{$file}: `pnpm {$script}` is not a script in package.json";
            }
        }
    }

    expect($violations)->toBe([], "Instruction files name scripts that do not exist:\n" . implode("\n", $violations));
});

it('links every port-trail detail file from its package README', function (): void {
    $root = documentationRepositoryRoot();
    $orphans = [];

    foreach (glob($root . '/docs/progress/*/README.md') ?: [] as $readme) {
        $index = documentationProse((string) file_get_contents($readme));

        foreach (glob(dirname($readme) . '/*.md') ?: [] as $detail) {
            $name = basename($detail);

            if ('README.md' !== $name && 1 !== preg_match('/\]\((?:\.\/)?' . preg_quote($name, '/') . '(?:#[^)]*)?\)/', $index)) {
                $orphans[] = str_replace($root . '/', '', $detail);
            }
        }
    }

    expect($orphans)->toBe([], "Detail files no package README links (docs/progress/README.md: one row per file):\n" . implode("\n", $orphans));
});
