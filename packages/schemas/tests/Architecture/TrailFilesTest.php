<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sylament\Schemas\Tests\Architecture\Trail\DetailFile;
use Sylament\Schemas\Tests\Architecture\Trail\Row;
use Sylament\Schemas\Tests\Architecture\Trail\Status;
use Sylament\Schemas\Tests\Architecture\Trail\Table;
use Sylament\Schemas\Tests\Architecture\Trail\Trail;
use UnexpectedValueException;

use function dirname;

/**
 * Convention test for the port trail under docs/progress/: frontmatter, ownership of every source file,
 * table and row rules, the generated README indexes, the trail paths code and docs/internals cite, and the
 * size alarm.
 * The logic is in Trail/; the rules are in .claude/rules/trail.md.
 */
function trailRepositoryRoot(): string
{
    return dirname(__DIR__, 4);
}

/**
 * A docs/progress path for a fixture, assembled so that the reference check reading this file finds no
 * reference to a trail file that does not exist.
 */
function trailDocs(string $relative): string
{
    return 'docs/progress/' . $relative;
}

/**
 * @param array<string, string> $files content keyed by path; a path ending in / is an empty directory
 */
function trailTree(array $files): string
{
    $root = sys_get_temp_dir() . '/' . uniqid('trail-', true);

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

function trailRemoveTree(string $root): void
{
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($root);
}

/**
 * @param list<string> $ports
 * @param list<string> $owns
 */
function trailFrontmatter(string $description, array $ports, array $owns): string
{
    $list = static fn (array $entries): string => [] === $entries ? ' []' : implode('', array_map(static fn (string $entry): string => "\n  - {$entry}", $entries));

    return "---\ndescription: {$description}\nports:{$list($ports)}\nowns:{$list($owns)}\n---\n";
}

/**
 * @param list<string> $rows each "| … |" line under the five-column header
 */
function trailSurface(array $rows): string
{
    return "## Surface\n\n| Feature | Ports | Status | Blocked by | Detail |\n| --- | --- | --- | --- | --- |\n" . implode("\n", $rows) . "\n";
}

/**
 * Two packages and three detail files that pass every check once the indexes are written.
 *
 * @return array<string, string>
 */
function trailGreenTree(): array
{
    $readme = static fn (string $title, string $unported): string => "# {$title} — port trail\n\nPorts it.\n\n## Files\n\n" . Trail::INDEX_START . "\n" . Trail::INDEX_END . "\n\n## Unported\n\n| Filament | Status | Blocked by | Detail |\n| --- | --- | --- | --- |\n{$unported}";

    return [
        'packages/alpha/src/Widget.php' => "<?php\n// The engine question is in " . trailDocs('alpha/widget.md') . ', section "Widget waits on the engine".' . "\n",
        'packages/alpha/src/Concerns/HasThing.php' => "<?php\n",
        'packages/alpha/templates/widget.html.twig' => "<div></div>\n",
        'packages/alpha/tests/WidgetTest.php' => "<?php\n",
        'packages/panels/src/Panel.php' => "<?php\n",
        'packages/panels/assets/src/panel.ts' => "export {};\n",
        'vendor/filament/alpha/src/Widget.php' => "<?php\n",
        'vendor/filament/alpha/src/Gadget.php' => "<?php\n",
        'vendor/filament/alpha/src/Concerns/HasThing.php' => "<?php\n",
        'vendor/filament/filament/src/Panel.php' => "<?php\n",
        'docs/internals/notes.md' => "# Notes\n\nSee `" . trailDocs('panels/panel.md') . "` (\"The panel waits on\nrouting\").\n",
        trailDocs('README.md') => "# Progress\n\n## Packages\n\n" . Trail::INDEX_START . "\nstale\n" . Trail::INDEX_END . "\n\n## Statuses\n",
        trailDocs('alpha/README.md') => $readme('Alpha', "| [`Gadget.php`](../../../vendor/filament/alpha/src/Gadget.php) | todo | — | — |\n"),
        trailDocs('panels/README.md') => $readme('Panels', ''),
        trailDocs('alpha/widget.md') => trailFrontmatter('The widget; its concerns are in has-thing.md.', ['vendor/filament/alpha/src/Widget.php'], ['packages/alpha/src/', 'packages/alpha/templates/widget.html.twig'])
            . "# Widget (alpha)\n\n" . trailSurface([
                '| `make()` | `Widget.php` | done | — | — |',
                '| `render()` | `Widget.php` | in progress | — | [detail](#widget-waits-on-the-engine) |',
                '| `mask()` | `Widget.php` | todo | — | — |',
            ]) . "\n## Details\n\n### `Widget` waits on the engine\n\nOne sentence.\n",
        trailDocs('alpha/has-thing.md') => trailFrontmatter('The HasThing concern.', ['vendor/filament/alpha/src/Concerns/'], ['packages/alpha/src/Concerns/'])
            . "# HasThing (alpha)\n\n" . trailSurface([
                '| `thing()` | `HasThing.php` | done | — | [semantics](#thing-is-subtle) |',
                '| `legacyThing()` | `HasThing.php` | skipped | — | — |',
            ]) . "\n## Semantics\n\n### Thing is subtle\n\nOne sentence.\n",
        trailDocs('panels/panel.md') => trailFrontmatter('The panel | its assets.', ['vendor/filament/filament/src/Panel.php'], ['packages/panels/src/Panel.php', 'packages/panels/assets/src/'])
            . "# Panel (panels)\n\n" . trailSurface([
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
function trailTracked(array $files): array
{
    return array_values(array_filter(array_keys($files), static fn (string $path): bool => !str_starts_with($path, 'vendor/') && !str_ends_with($path, '/')));
}

it('rolls row statuses up', function (array $statuses, bool $unaudited, Status $expected): void {
    expect(Trail::rollup($statuses, $unaudited))->toBe($expected);
})->with([
    'no statuses, unaudited' => [[], true, Status::Todo],
    'all skipped' => [[Status::Skipped, Status::Skipped], false, Status::Skipped],
    'all skipped, unaudited' => [[Status::Skipped], true, Status::Todo],
    'done and skipped' => [[Status::Done, Status::Skipped], false, Status::Done],
    'all done' => [[Status::Done], false, Status::Done],
    'all done, unaudited' => [[Status::Done], true, Status::InProgress],
    'all todo' => [[Status::Todo, Status::Todo], false, Status::Todo],
    'all todo, unaudited' => [[Status::Todo], true, Status::Todo],
    'blocked and todo' => [[Status::Blocked, Status::Todo], false, Status::Blocked],
    'blocked and skipped' => [[Status::Blocked, Status::Skipped], false, Status::Blocked],
    'todo and skipped' => [[Status::Todo, Status::Skipped], false, Status::Todo],
    'done and todo' => [[Status::Done, Status::Todo], false, Status::InProgress],
    'done and blocked' => [[Status::Done, Status::Blocked], false, Status::InProgress],
    'in progress alone' => [[Status::InProgress], false, Status::InProgress],
    'in progress and blocked' => [[Status::InProgress, Status::Blocked], false, Status::InProgress],
]);

it('refuses to roll up nothing', function (): void {
    Trail::rollup([], false);
})->throws(UnexpectedValueException::class, 'has no Surface row');

it('reads a Surface table into rows with their lines', function (): void {
    $markdown = "# X (forms)\n\n" . trailSurface([
        '| [`a()`](../a.php) | [`A.php`](../A.php) | done | — | — |',
        '| `b() \| c()` | — | blocked | a masking engine | [detail](#b) · [diverges](other.md#c) |',
    ]) . "\n## Details\n\n| Not | A | Surface |\n| --- | --- | --- |\n";

    expect(Trail::tables($markdown, 'Surface'))->toEqual([
        new Table('Surface', [
            new Row('[`a()`](../a.php)', '[`A.php`](../A.php)', Status::Done, '—', '—', 7),
            new Row('`b() \| c()`', '—', Status::Blocked, 'a masking engine', '[detail](#b) · [diverges](other.md#c)', 8),
        ], false, 5),
    ]);
});

it('keeps the unaudited row out of the rows and flags the table', function (): void {
    $tables = Trail::tables(trailSurface(['| `a()` | — | todo | — | — |', '| _rest not audited yet_ | — | — | — | — |']), 'Surface');

    expect($tables)->toHaveCount(1)
        ->and($tables[0]->unaudited)->toBeTrue()
        ->and($tables[0]->rows)->toHaveCount(1);
});

it('reads every section whose heading starts with Surface, and skips fenced tables', function (): void {
    $markdown = "## Surface\n\n```\n| Feature | Ports | Status | Blocked by | Detail |\n```\n\n| Feature | Ports | Status | Blocked by | Detail |\n| --- | --- | --- | --- | --- |\n| `a()` | — | done | — | — |\n\n"
        . "## Surface — FileUpload\n\n| Feature | Ports | Status | Blocked by | Detail |\n| --- | --- | --- | --- | --- |\n| _not audited yet_ | — | — | — | — |\n";

    $tables = Trail::tables($markdown, 'Surface');

    expect(array_map(static fn (Table $table): array => [$table->section, $table->line, count($table->rows), $table->unaudited], $tables))
        ->toBe([['Surface', 7, 1, false], ['Surface — FileUpload', 13, 0, true]]);
});

it('reads an Unported table, whose rows have no Ports cell', function (): void {
    $markdown = "## Unported\n\n| Filament | Status | Blocked by | Detail |\n| --- | --- | --- | --- |\n| [`X.php`](../x) | skipped | — | [why](#why) |\n";

    expect(Trail::tables($markdown, 'Unported')[0]->rows)->toEqual([new Row('[`X.php`](../x)', '', Status::Skipped, '—', '[why](#why)', 5)]);
});

it('rejects a malformed table', function (string $markdown, string $message): void {
    expect(static fn (): array => Trail::tables($markdown, 'Surface'))->toThrow(UnexpectedValueException::class, $message);
})->with([
    'the four-column header' => [
        "## Surface\n\n| Feature | Status | Blocked by | Detail |\n| --- | --- | --- | --- |\n| `a()` | done | — | — |\n",
        '3: the table header is "| Feature | Status | Blocked by | Detail |", not "| Feature | Ports | Status | Blocked by | Detail |"',
    ],
    'two tables under ### headings' => [
        "## Surface\n\n### One\n\n| Feature | Ports | Status | Blocked by | Detail |\n| --- | --- | --- | --- | --- |\n| `a()` | — | done | — | — |\n\n### Two\n\n| Feature | Ports | Status | Blocked by | Detail |\n| --- | --- | --- | --- | --- |\n| `b()` | — | done | — | — |\n",
        '1: ## Surface holds 2 tables; it holds exactly one',
    ],
    'a section without a table' => ["## Surface\n\nNothing yet.\n", '1: ## Surface holds 0 tables; it holds exactly one'],
    'a status outside the vocabulary' => [trailSurface(['| `a()` | — | **done** | — | — |']), '5: "**done**" is not a status in the vocabulary'],
    'a short row' => [trailSurface(['| `a()` | — | done | — |']), '5: the row has 4 cells and the header 5'],
    'an unpipe-escaped code span' => [trailSurface(['| `a | b` | — | done | — | — |']), '5: the row has 6 cells and the header 5'],
    'an unaudited row with blank cells' => [trailSurface(['| _not audited yet_ | | | | |']), '5: an unaudited row has — in every other cell'],
    'no separator row' => ["## Surface\n\n| Feature | Ports | Status | Blocked by | Detail |\n| `a()` | — | done | — | — |\n", '3: the table has no --- separator row under its header'],
]);

it('parses frontmatter', function (): void {
    expect(Trail::frontmatter(trailFrontmatter('The field.', [], ['packages/forms/src/X.php']) . "# X\n"))
        ->toBe(['description' => 'The field.', 'ports' => [], 'owns' => ['packages/forms/src/X.php']]);
});

it('rejects a file without frontmatter or with a YAML comment', function (string $markdown, string $message): void {
    expect(static fn (): array => Trail::frontmatter($markdown))->toThrow(UnexpectedValueException::class, $message);
})->with([
    'no frontmatter' => ["# X (forms)\n", 'has no frontmatter'],
    'a comment line' => ["---\n# drafted\ndescription: X.\nports: []\nowns: []\n---\n", 'line 2: the frontmatter holds a YAML comment'],
    'a trailing comment on a list entry' => ["---\ndescription: X.\nports: []\nowns:\n  - packages/a/src/X.php # check\n---\n", 'line 5: the frontmatter holds a YAML comment'],
    'a trailing comment on a value' => ["---\ndescription: X. # draft\nports: []\nowns: []\n---\n", 'line 2: the frontmatter holds a YAML comment'],
    'invalid YAML' => ["---\ndescription: a: b\nports: []\nowns: []\n---\n", 'the frontmatter is not valid YAML'],
    'a list' => ["---\n- a\n---\n", 'the frontmatter is not a YAML mapping'],
]);

it('accepts a # that is not a YAML comment', function (string $line): void {
    expect(Trail::frontmatter("---\n{$line}\nports: []\nowns: []\n---\n"))->toHaveKey('description');
})->with([
    'inside double quotes' => ['description: "the # sign"'],
    'inside single quotes' => ["description: 'the # sign'"],
    'glued to a word' => ['description: the `#[Required]` setter'],
]);

it('lists what a frontmatter breaks', function (array $frontmatter, array $violations): void {
    $root = trailTree(['vendor/filament/forms/src/Field.php' => '', 'vendor/filament/forms/src/Concerns/' => '']);

    try {
        expect(new Trail($root, [])->frontmatterViolations($frontmatter))->toBe($violations);
    } finally {
        trailRemoveTree($root);
    }
})->with([
    'valid' => [['description' => 'X.', 'ports' => ['vendor/filament/forms/src/Field.php', 'vendor/filament/forms/src/Concerns/'], 'owns' => []], []],
    'a missing key' => [['description' => 'X.', 'ports' => []], ['the frontmatter lacks owns']],
    'an extra key' => [['description' => 'X.', 'ports' => [], 'owns' => [], 'summary' => 'Y.'], ['the frontmatter holds only description, ports and owns, not summary']],
    'a two-line description' => [['description' => "X.\nY.", 'ports' => [], 'owns' => []], ['description is one non-empty line']],
    'an empty description' => [['description' => ' ', 'ports' => [], 'owns' => []], ['description is one non-empty line']],
    'ports as a string' => [['description' => 'X.', 'ports' => 'Field.php', 'owns' => null], ['ports is a list of paths, [] when empty', 'owns is a list of paths, [] when empty']],
    'bad ports entries' => [
        ['description' => 'X.', 'ports' => ['packages/forms/src/X.php', 'vendor/filament/forms/src/Gone.php', 'vendor/filament/forms/src/Concerns', 'vendor/filament/forms/src/Field.php/'], 'owns' => []],
        [
            'ports entry packages/forms/src/X.php is not under vendor/filament/',
            'ports entry vendor/filament/forms/src/Gone.php does not exist',
            'ports entry vendor/filament/forms/src/Concerns is a directory, so it ends with /',
            'ports entry vendor/filament/forms/src/Field.php/ is a file, so it does not end with /',
        ],
    ],
]);

it('holds a row to the Blocked by and Detail rules', function (Status $status, string $blockedBy, string $detail, array $violations): void {
    expect(Trail::rowViolations(new Row('`a()`', '—', $status, $blockedBy, $detail, 7)))->toBe($violations);
})->with([
    'a dash' => [Status::Done, '—', '—', []],
    'one link' => [Status::Done, '—', '[detail](#x)', []],
    'links joined by the middle dot' => [Status::Done, '—', '[semantics](#x) · [diverges](../renames.md#y) · [z](z.md)', []],
    'prose' => [Status::Done, '—', 'see [detail](#x)', ['7: the Detail cell holds only links joined by " · ", or —, not "see [detail](#x)"']],
    'a comma between links' => [Status::Done, '—', '[a](#x), [b](#y)', ['7: the Detail cell holds only links joined by " · ", or —, not "[a](#x), [b](#y)"']],
    'an empty cell' => [Status::Done, '—', '', ['7: the Detail cell holds only links joined by " · ", or —, not ""']],
    'a blocked row naming its prerequisite' => [Status::Blocked, 'a masking engine', '—', []],
    'a blocked row naming none' => [Status::Blocked, '—', '—', ['7: a blocked row names its prerequisite in Blocked by']],
    'a todo row naming a blocker' => [Status::Todo, 'the layout columns', '—', ['7: a row that is todo has — in Blocked by, not "the layout columns"']],
]);

it('renders the Ports cell of an index row', function (array $ports, string $package, string $cell): void {
    expect(Trail::renderPorts($ports, $package))->toBe($cell);
})->with([
    'none' => [[], 'forms', '—'],
    'a file and a directory' => [['vendor/filament/forms/src/Components/TextInput.php', 'vendor/filament/forms/src/Components/TextInput/Actions/'], 'forms', '`Components/TextInput.php`, `Components/TextInput/Actions/`'],
    'panels maps to filament' => [['vendor/filament/filament/src/Panel.php'], 'panels', '`Panel.php`'],
    'outside src' => [['vendor/filament/forms/resources/views/components/text-input.blade.php'], 'forms', '`resources/views/components/text-input.blade.php`'],
    'another package' => [['vendor/filament/support/src/Concerns/HasColor.php'], 'forms', '`vendor/filament/support/src/Concerns/HasColor.php`'],
    'the whole source tree' => [['vendor/filament/forms/src/'], 'forms', '`vendor/filament/forms/src/`'],
]);

it('renders the Open cell of an index row', function (int $inProgress, int $blocked, int $todo, bool $unaudited, string $cell): void {
    expect(Trail::renderOpen(['inProgress' => $inProgress, 'blocked' => $blocked, 'todo' => $todo, 'unaudited' => $unaudited]))->toBe($cell);
})->with([
    'nothing open' => [0, 0, 0, false, '—'],
    'every kind' => [2, 1, 3, false, '2 in progress · 1 blocked · 3 todo'],
    'zeros omitted' => [0, 0, 1, false, '1 todo'],
    'unaudited appended' => [1, 0, 0, true, '1 in progress · unaudited'],
    'only unaudited' => [0, 0, 0, true, 'unaudited'],
]);

it('counts open rows and rolls a file up', function (): void {
    $file = new DetailFile('x.md', 'forms', 'X.', [], [], [
        new Table('Surface', [new Row('a', '—', Status::Done, '—', '—', 1), new Row('b', '—', Status::Todo, '—', '—', 2)], false, 1),
        new Table('Surface — B', [new Row('c', '—', Status::Blocked, 'y', '—', 3)], true, 5),
    ], ['Surface', 'Surface — B']);

    expect($file->status())->toBe(Status::InProgress)
        ->and($file->open())->toBe(['inProgress' => 0, 'blocked' => 1, 'todo' => 1, 'unaudited' => true]);
});

it('resolves every ownable file to the longest entry that matches it', function (): void {
    $owns = static fn (array $entries): string => trailFrontmatter('X.', [], $entries) . "# X (alpha)\n";
    $files = [
        'packages/alpha/src/X.php' => '',
        'packages/alpha/src/Sub/Y.php' => '',
        'packages/alpha/src/Z.php' => '',
        'packages/alpha/templates/t.html.twig' => '',
        'packages/alpha/tests/XTest.php' => '',
        'packages/beta/src/' => '',
        trailDocs('alpha/one.md') => $owns(['packages/alpha/src/']),
        trailDocs('alpha/two.md') => $owns(['packages/alpha/src/Sub/', 'packages/alpha/src/Gone.php', 'packages/alpha/composer.json', 'packages/alpha/src/Sub']),
        trailDocs('alpha/three.md') => $owns(['packages/alpha/src/Z.php']),
        trailDocs('alpha/four.md') => $owns(['packages/alpha/src/Z.php']),
        trailDocs('alpha/five.md') => "# No frontmatter (alpha)\n",
    ];
    $root = trailTree($files);

    try {
        expect(new Trail($root, trailTracked($files))->ownership())->toBe([
            'owners' => [
                'packages/alpha/src/X.php' => trailDocs('alpha/one.md'),
                'packages/alpha/src/Sub/Y.php' => trailDocs('alpha/two.md'),
            ],
            'violations' => [
                'unreadable owns: ' . trailDocs('alpha/five.md') . ' declares no owns list, so it owns nothing',
                'not ownable: ' . trailDocs('alpha/two.md') . ': packages/alpha/composer.json is outside packages/*/src/, templates/ and assets/src/',
                'unowned: packages/alpha/templates/t.html.twig',
                'owned twice: packages/alpha/src/Z.php is in ' . trailDocs('alpha/four.md') . ', ' . trailDocs('alpha/three.md'),
                'stale entry: ' . trailDocs('alpha/two.md') . ': packages/alpha/src/Gone.php matches no tracked ownable file',
                'stale entry: ' . trailDocs('alpha/two.md') . ': packages/alpha/src/Sub matches no tracked ownable file (a directory entry ends with /)',
            ],
        ]);
    } finally {
        trailRemoveTree($root);
    }
});

it('checks the trail paths and quoted titles that code and docs/internals cite', function (): void {
    $files = [
        trailDocs('alpha/widget.md') => "# Widget (alpha)\n\n## Details\n\n### `fill()` resurrects its pre-fill items\n",
        'packages/alpha/src/Ref.php' => "<?php\n/*\n * Recorded in `" . trailDocs('alpha/widget.md') . "` (\"fill() resurrects its\n * pre-fill items\"), and in " . trailDocs('alpha/gone.md') . ".\n */\n",
        'docs/internals/notes.md' => "# Notes\n\nSee `" . trailDocs('alpha/widget.md') . "`, section \"Details\".\nSee `" . trailDocs('alpha/widget.md') . "`, \"A heading that is gone\".\nSee " . trailDocs('alpha/widget.md') . ' ("Gadget") too.' . "\n",
        'docs/internals/deep/skipped.md' => trailDocs('alpha/gone.md') . "\n",
    ];
    $root = trailTree($files);

    try {
        expect(new Trail($root, trailTracked($files))->referenceViolations())->toBe([
            'packages/alpha/src/Ref.php:4: names ' . trailDocs('alpha/gone.md') . ', which does not exist',
            'docs/internals/notes.md:4: ' . trailDocs('alpha/widget.md') . ' has no heading containing "A heading that is gone"',
            'docs/internals/notes.md:5: ' . trailDocs('alpha/widget.md') . ' has no heading containing "Gadget"',
        ]);
    } finally {
        trailRemoveTree($root);
    }
});

it('raises the size alarm one byte past 90 KB', function (int $bytes, bool $alarm): void {
    $files = trailGreenTree();
    $head = "# Big (alpha)\n\n";
    $files[trailDocs('alpha/big.md')] = $head . str_repeat('x', $bytes - mb_strlen($head, '8bit'));
    $root = trailTree($files);

    try {
        expect(new Trail($root, trailTracked($files))->sizeViolations())
            ->toBe($alarm ? [trailDocs('alpha/big.md') . ": {$bytes} bytes, past the 90 KB alarm — split it along the code it owns"] : []);
    } finally {
        trailRemoveTree($root);
    }
})->with([
    'at the alarm' => [Trail::SIZE_ALARM_BYTES, false],
    'one byte past it' => [Trail::SIZE_ALARM_BYTES + 1, true],
]);

it('writes the indexes between the markers and passes every check afterwards', function (): void {
    $files = trailGreenTree();
    $root = trailTree($files);
    $trail = new Trail($root, trailTracked($files));

    try {
        expect($trail->indexViolations())->toBe([
            trailDocs('README.md') . ': the trail index is stale; run `composer docs:index`',
            trailDocs('alpha/README.md') . ': the trail index is stale; run `composer docs:index`',
            trailDocs('panels/README.md') . ': the trail index is stale; run `composer docs:index`',
        ])->and($trail->writeIndexes())->toBe([trailDocs('README.md'), trailDocs('alpha/README.md'), trailDocs('panels/README.md')])
            ->and($trail->writeIndexes())->toBe([])
            ->and($trail->formatViolations())->toBe([])
            ->and($trail->ownership()['violations'])->toBe([])
            ->and($trail->indexViolations())->toBe([])
            ->and($trail->referenceViolations())->toBe([])
            ->and($trail->sizeViolations())->toBe([])
            ->and((string) file_get_contents($root . '/' . trailDocs('README.md')))->toContain(implode("\n", [
                Trail::INDEX_START,
                '| Package | Ports | Status | Detail |',
                '| --- | --- | --- | --- |',
                '| [`alpha`](../../packages/alpha) | [`vendor/filament/alpha`](../../vendor/filament/alpha) | in progress | [alpha](alpha/README.md) |',
                '| [`panels`](../../packages/panels) | [`vendor/filament/filament`](../../vendor/filament/filament) | blocked | [panels](panels/README.md) |',
                Trail::INDEX_END,
                '',
                '## Statuses',
            ]))
            ->and((string) file_get_contents($root . '/' . trailDocs('alpha/README.md')))->toContain(implode("\n", [
                Trail::INDEX_START,
                '| File | Status | Open | Ports | Description |',
                '| --- | --- | --- | --- | --- |',
                '| [has-thing.md](has-thing.md) | done | — | `Concerns/` | The HasThing concern. |',
                '| [widget.md](widget.md) | in progress | 1 in progress · 1 todo | `Widget.php` | The widget; its concerns are in has-thing.md. |',
                Trail::INDEX_END,
                '',
                '## Unported',
            ]))
            ->and($trail->renderPackageIndex('panels'))->toEndWith('| [panel.md](panel.md) | blocked | 1 blocked · unaudited | `Panel.php` | The panel \| its assets. |')
            ->and($trail->unported('alpha'))->toHaveCount(1)
            ->and($trail->unported('panels'))->toBe([])
            ->and(array_map(count(...), $trail->detailFiles()))->toBe(['alpha' => 2, 'panels' => 1]);
    } finally {
        trailRemoveTree($root);
    }
});

it('names composer docs:index when an index block is edited by hand', function (): void {
    $files = trailGreenTree();
    $root = trailTree($files);
    $trail = new Trail($root, trailTracked($files));

    try {
        $trail->writeIndexes();
        $readme = $root . '/' . trailDocs('alpha/README.md');
        file_put_contents($readme, str_replace('| done |', '| in progress |', (string) file_get_contents($readme)));

        expect($trail->indexViolations())->toBe([trailDocs('alpha/README.md') . ': the trail index is stale; run `composer docs:index`']);
    } finally {
        trailRemoveTree($root);
    }
});

it('refuses to write when a README lacks its markers, and writes nothing', function (string $block): void {
    $files = trailGreenTree();
    $files[trailDocs('panels/README.md')] = "# Panels — port trail\n\n## Files\n\n{$block}\n## Unported\n\n| Filament | Status | Blocked by | Detail |\n| --- | --- | --- | --- |\n";
    $root = trailTree($files);

    try {
        expect(static fn (): array => new Trail($root, trailTracked($files))->writeIndexes())
            ->toThrow(UnexpectedValueException::class, trailDocs('panels/README.md') . ' needs exactly one ' . Trail::INDEX_START . ' … ' . Trail::INDEX_END . ' pair')
            ->and((string) file_get_contents($root . '/' . trailDocs('README.md')))->toBe($files[trailDocs('README.md')]);
    } finally {
        trailRemoveTree($root);
    }
})->with([
    'no markers' => [''],
    'two pairs' => [Trail::INDEX_START . "\n" . Trail::INDEX_END . "\n" . Trail::INDEX_START . "\n" . Trail::INDEX_END . "\n"],
    'end before start' => [Trail::INDEX_END . "\n" . Trail::INDEX_START . "\n"],
]);

it('refuses a package with no detail file and no Unported row', function (): void {
    $files = trailGreenTree();
    unset($files[trailDocs('panels/panel.md')]);
    $root = trailTree($files);

    try {
        expect(static fn (): Status => new Trail($root, trailTracked($files))->packageStatus('panels'))
            ->toThrow(UnexpectedValueException::class, trailDocs('panels/README.md') . ': the package has no detail file and no Unported row');
    } finally {
        trailRemoveTree($root);
    }
});

it('lists every format violation of a broken trail', function (): void {
    $files = trailGreenTree();
    $files[trailDocs('alpha/widget.md')] = "# Widget (alpha)\n\n**Status:** done\n\n## Surface\n\n| Feature | Status | Blocked by | Detail |\n| --- | --- | --- | --- |\n| `a()` | done | — | see below |\n\n## Details\n";
    $files[trailDocs('alpha/has-thing.md')] = trailFrontmatter('X.', ['vendor/filament/alpha/src/Gone.php'], []) . "# HasThing (alpha)\n\n" . trailSurface(['| `a()` | — | skipped | — | — |']) . "\n## Details\n\n### Stale\n";
    $files[trailDocs('alpha/empty.md')] = trailFrontmatter('X.', [], []) . "# Empty (alpha)\n";
    $files[trailDocs('alpha/README.md')] = "# Alpha\n\n## Unported\n\n| Filament | Status | Blocked by | Detail |\n| --- | --- | --- | --- |\n| `Gadget` | blocked | — | — |\n| [`Gone.php`](../../../vendor/filament/alpha/src/Gone.php) | todo | — | — |\n";
    $files[trailDocs('panels/README.md')] = "# Panels\n";
    $root = trailTree($files);

    try {
        expect(new Trail($root, trailTracked($files))->formatViolations())->toBe([
            trailDocs('alpha/empty.md') . ': has no Surface row',
            trailDocs('alpha/has-thing.md') . ': ports entry vendor/filament/alpha/src/Gone.php does not exist',
            trailDocs('alpha/has-thing.md') . ': a skipped file has no ## Details section',
            trailDocs('alpha/widget.md') . ': has no frontmatter (a --- block on its first line)',
            trailDocs('alpha/widget.md') . ':7: the table header is "| Feature | Status | Blocked by | Detail |", not "| Feature | Ports | Status | Blocked by | Detail |"',
            trailDocs('alpha/widget.md') . ':9: the Detail cell holds only links joined by " · ", or —, not "see below"',
            trailDocs('alpha/README.md') . ':7: a blocked row names its prerequisite in Blocked by',
            trailDocs('alpha/README.md') . ':7: the Filament cell is one link to an existing vendor/filament/ path, not "`Gadget`"',
            trailDocs('alpha/README.md') . ':7: a blocked Unported row links its reason from Detail',
            trailDocs('alpha/README.md') . ':8: the Filament cell is one link to an existing vendor/filament/ path, not "[`Gone.php`](../../../vendor/filament/alpha/src/Gone.php)"',
            trailDocs('panels/README.md') . ': holds 0 ## Unported sections; it holds exactly one',
            trailDocs('alpha/widget.md') . ':3: a **Status:** line; status lives on rows and the generated indexes',
        ]);
    } finally {
        trailRemoveTree($root);
    }
});

it('gives every detail file valid frontmatter and well-formed tables, and no trail file a Status line (checks 1, 3, 4, 5)', function (): void {
    $violations = new Trail(trailRepositoryRoot())->formatViolations();

    expect($violations)->toBe([], "Trail format violations (.claude/rules/trail.md):\n" . implode("\n", $violations));
});

it('gives every ownable file exactly one owner (check 2)', function (): void {
    $violations = new Trail(trailRepositoryRoot())->ownership()['violations'];

    expect($violations)->toBe([], "Ownership violations: add the path to the owns of the trail file that documents it:\n" . implode("\n", $violations));
});

it('keeps every generated index current (check 6)', function (): void {
    $violations = new Trail(trailRepositoryRoot())->indexViolations();

    expect($violations)->toBe([], "Stale trail indexes; run `composer docs:index`:\n" . implode("\n", $violations));
});

it('names only trail paths and headings that exist (check 7)', function (): void {
    $violations = new Trail(trailRepositoryRoot())->referenceViolations();

    expect($violations)->toBe([], "References to trail files or sections that do not exist:\n" . implode("\n", $violations));
});

it('keeps every trail file under the 90 KB alarm (check 9)', function (): void {
    $violations = new Trail(trailRepositoryRoot())->sizeViolations();

    expect($violations)->toBe([], "Trail files past the size alarm (.claude/rules/trail.md):\n" . implode("\n", $violations));
});
