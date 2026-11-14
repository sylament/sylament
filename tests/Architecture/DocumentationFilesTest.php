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

it('finds a named path that does not exist', function (): void {
    expect(DocumentationFiles::missingPaths('`CLAUDE.md`, `docs/internals/no-such-file.md`, `packages/*/src/` and `docs/superpowers/plan.md`'))
        ->toBe(['docs/internals/no-such-file.md']);
});

it('names only repository paths that exist in the instruction files', function (): void {
    $root = DocumentationFiles::repositoryRoot();
    $violations = [];

    foreach (DocumentationFiles::instructionFiles() as $file) {
        foreach (DocumentationFiles::missingPaths((string) file_get_contents($root . '/' . $file)) as $path) {
            $violations[] = "{$file}: `{$path}` does not exist";
        }
    }

    expect($violations)->toBe([], "Instruction files name paths that do not exist:\n" . implode("\n", $violations));
});

it('finds a named composer or pnpm script that does not exist', function (): void {
    expect(DocumentationFiles::unknownScripts('`composer test`, `composer test:nope`, `pnpm build` and `pnpm test:nope`'))
        ->toBe(['`composer test:nope` is not a script in composer.json', '`pnpm test:nope` is not a script in package.json']);
});

it('names only composer and pnpm scripts that exist in the instruction files', function (): void {
    $root = DocumentationFiles::repositoryRoot();
    $violations = [];

    foreach (DocumentationFiles::instructionFiles() as $file) {
        foreach (DocumentationFiles::unknownScripts((string) file_get_contents($root . '/' . $file)) as $script) {
            $violations[] = "{$file}: {$script}";
        }
    }

    expect($violations)->toBe([], "Instruction files name scripts that do not exist:\n" . implode("\n", $violations));
});
