<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Documentation\DocumentationFiles;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Convention test for the repository's markdown: CLAUDE.md, .claude/ and docs/. It holds what a machine
 * can prove about documentation — every relative link and anchor resolves, the instruction files name
 * only paths and scripts that exist, and docs/ carries no milestone label. Whether a sentence is still
 * true is a judgment, which the docs-audit skill makes at a milestone's end.
 */

it('turns headings into GitHub anchors', function (string $heading, string $anchor): void {
    expect(DocumentationFiles::slug($heading))->toBe($anchor);
})->with([
    'plain words' => ['The component model', 'the-component-model'],
    'code and punctuation' => ["`evaluate()`'s generic", 'evaluates-generic'],
    'a numbered heading' => ['1. Symfony idiom, not Laravel idiom', '1-symfony-idiom-not-laravel-idiom'],
    'a question' => ['How do the two attribute bags render a value?', 'how-do-the-two-attribute-bags-render-a-value'],
    'an accented letter' => ['Čo je nové', 'čo-je-nové'],
]);

it('finds a broken link and a broken anchor', function (): void {
    $root = WorkerDirectory::path() . '/' . uniqid('documentation-probe-', true);
    new Filesystem()->dumpFile($root . '/docs/internals/principles.md', "# Principles\n");
    new Filesystem()->dumpFile($root . '/docs/probe.md', "# Probe `Code` heading\n\n[here](#probe-code-heading) [gone](no-such-file.md) [bad](internals/principles.md#no-such-heading) [ok](internals/principles.md) `[code](ignored.md)`\n");

    try {
        expect(DocumentationFiles::brokenLinks($root, 'docs/probe.md'))->toBe(['no-such-file.md (no such file)', 'internals/principles.md#no-such-heading (no such heading)']);
    } finally {
        new Filesystem()->remove($root);
    }
});

it('resolves every relative link and anchor in CLAUDE.md, .claude/ and docs/', function (): void {
    $violations = [];

    foreach (DocumentationFiles::markdownFiles() as $file) {
        foreach (DocumentationFiles::brokenLinks(DocumentationFiles::repositoryRoot(), $file) as $link) {
            $violations[] = "{$file}: {$link}";
        }
    }

    expect($violations)->toBe([], "Broken documentation links:\n" . implode("\n", $violations));
});

it('finds the milestone labels docs/ must not carry', function (string $content, array $labels): void {
    expect(DocumentationFiles::milestoneLabels($content))->toBe($labels);
})->with([
    'a milestone tag' => ['since M7 · 5 the table', ['M7 · 5']],
    'a compact tag' => ['the M4·6 grouping', ['M4·6']],
    'a bare milestone' => ['the sweep that closed M3', ['M3']],
    'a code span' => ['the `M7 · 5` example', []],
]);

it('keeps milestone labels out of docs/', function (): void {
    $root = DocumentationFiles::repositoryRoot();
    $violations = [];

    foreach (DocumentationFiles::markdownFiles() as $file) {
        if (!str_starts_with($file, 'docs/')) {
            continue;
        }

        $labels = DocumentationFiles::milestoneLabels((string) file_get_contents($root . '/' . $file));

        if ([] !== $labels) {
            $violations[] = "{$file}: " . implode(', ', $labels);
        }
    }

    expect($violations)->toBe([], "Milestone labels in docs/ (state the behaviour or the missing capability instead):\n" . implode("\n", $violations));
});

it('names only repository paths that exist in the instruction files', function (): void {
    $root = DocumentationFiles::repositoryRoot();
    $violations = [];

    foreach (DocumentationFiles::instructionFiles() as $file) {
        preg_match_all('/`((?:packages|docs|\.claude|tests|vendor\/filament)\/[^`\s]*|CLAUDE\.md|composer\.json|package\.json|pnpm-workspace\.yaml|biome\.json|tsconfig\.base\.json)`/', (string) file_get_contents($root . '/' . $file), $matches);

        foreach (array_unique($matches[1]) as $path) {
            if (1 === preg_match('/[*{}<>$]|\.\.\./', $path) || file_exists($root . '/' . mb_rtrim($path, '/')) || DocumentationFiles::isIgnored($path)) {
                continue;
            }

            $violations[] = "{$file}: `{$path}` does not exist";
        }
    }

    expect($violations)->toBe([], "Instruction files name paths that do not exist:\n" . implode("\n", $violations));
});

it('names only composer and pnpm scripts that exist in the instruction files', function (): void {
    $root = DocumentationFiles::repositoryRoot();
    $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $pnpm = json_decode((string) file_get_contents($root . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
    $composerScripts = is_array($composer) && is_array($composer['scripts'] ?? null) ? array_keys($composer['scripts']) : [];
    $pnpmScripts = is_array($pnpm) && is_array($pnpm['scripts'] ?? null) ? array_keys($pnpm['scripts']) : [];
    $violations = [];

    foreach (DocumentationFiles::instructionFiles() as $file) {
        $content = (string) file_get_contents($root . '/' . $file);
        preg_match_all('/`composer ((?:test|format|docs)(?::[a-z-]+)?)\b/', $content, $composerNames);
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
