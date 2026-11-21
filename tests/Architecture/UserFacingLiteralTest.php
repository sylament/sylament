<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Translations\LiteralScan;
use Sylament\Tests\Architecture\Translations\Packages;
use Sylament\Tests\Architecture\Translations\PlantedPackages;

it('reports template text, a text attribute, a text-setter literal and a TS string default', function (): void {
    $directory = PlantedPackages::plant([
        'support/templates/components/Empty/index.html.twig' => "<p class=\"sy-empty\">\n    Nothing here\n</p>",
        'support/templates/components/Close/index.html.twig' => '<button type="button" aria-label="Close"></button>',
        'support/src/Components/Save.php' => "<?php\n\$action->label('Save');\n",
        'support/assets/src/controllers/clipboard_controller.ts' => "static values = {\n  message: { type: String, default: 'Copied' },\n};\n",
    ]);

    try {
        expect(LiteralScan::literals($this::getContainer()->get('twig'), 'support', $directory))->toBe([
            'assets/src/controllers/clipboard_controller.ts: Copied',
            'src/Components/Save.php: Save',
            'templates/components/Close/index.html.twig: Close',
            'templates/components/Empty/index.html.twig: Nothing here',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a literal inside a setter closure, a getter fallback and a Twig join', function (): void {
    $directory = PlantedPackages::plant([
        'support/src/Components/Create.php' => "<?php\n\$action->label(fn (): string => 'Create');\n",
        'support/src/Components/Heading.php' => "<?php\nclass Heading\n{\n    public function getHeading(): string\n    {\n        return \$this->heading ?? 'Untitled';\n    }\n}\n",
        'support/templates/components/Search/index.html.twig' => "<input placeholder=\"{{ 'Search ' ~ label }}\">",
        'support/templates/components/Toast/index.html.twig' => '<twig:SylamentSupport:IconButton icon="heroicons:x-mark" label="Close" />',
    ]);

    try {
        expect(LiteralScan::literals($this::getContainer()->get('twig'), 'support', $directory))->toBe([
            'src/Components/Create.php: Create',
            'src/Components/Heading.php: Untitled',
            'templates/components/Search/index.html.twig: Search',
            'templates/components/Toast/index.html.twig: Close',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a text property default, a text-keyed array entry, a formatted fallback, a text-named getter and a TS text binding', function (): void {
    $directory = PlantedPackages::plant([
        'support/src/Pages/CreatePage.php' => "<?php\nclass CreatePage\n{\n    protected function getCreatedNotificationTitle(): ?string\n    {\n        return 'Created';\n    }\n}\n",
        'support/src/Pages/Dashboard.php' => "<?php\nclass Dashboard\n{\n    protected static ?string \$title = 'Dashboard';\n}\n",
        'support/src/Components/Editor.php' => "<?php\nclass Editor\n{\n    public function getEmptyStateHeading(): string\n    {\n        return \$this->evaluate(\$this->emptyStateHeading) ?? sprintf('No %s', \$this->getPluralRecordLabel());\n    }\n\n    public function getActions(): array\n    {\n        return [['label' => 'Zoom in', 'method' => 'editorZoom']];\n    }\n}\n",
        'support/src/Summarizers/Sum.php' => "<?php\nclass Sum\n{\n    public function getDefaultLabel(): string\n    {\n        return 'Sum';\n    }\n}\n",
        'support/assets/src/controllers/wizard_controller.ts' => "const COMPLETED_TEXT = \"Completed\";\nconstructor({ loadingMessage = \"Loading...\", placement = \"bottom-start\" }) {}\n",
    ]);

    try {
        expect(LiteralScan::literals($this::getContainer()->get('twig'), 'support', $directory))->toBe([
            'assets/src/controllers/wizard_controller.ts: Completed',
            'assets/src/controllers/wizard_controller.ts: Loading...',
            'src/Components/Editor.php: No %s',
            'src/Components/Editor.php: Zoom in',
            'src/Pages/CreatePage.php: Created',
            'src/Pages/Dashboard.php: Dashboard',
            'src/Summarizers/Sum.php: Sum',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('leaves translated text, keys, classes, comparisons and tokens alone', function (): void {
    $directory = PlantedPackages::plant([
        'support/src/Components/Create.php' => <<<'PHP'
            <?php
            use Symfony\Component\Translation\TranslatableMessage;
            $action->label(fn (): TranslatableMessage => new TranslatableMessage('create.single.label', ['label' => $action->getRecordLabel()], 'sylament-actions'));
            $action->heading(fn (): string => $table->hasAction('create') ? throw new LogicException('Developer text.') : $state);
            $action->modalHeading(fn (): ?TranslatableMessage => match ($format) {
                'csv' => new TranslatableMessage('export.csv.label', ['shown' => '__COUNT__'], 'sylament-actions'),
                default => null,
            });
            PHP,
        'support/templates/components/Count/index.html.twig' => <<<'TWIG'
            <span class="sy-a sy-b" {{ stimulus_action('sylament--support--section', 'toggleFromButton') }}>{{ 'k'|trans({shown: '__COUNT__'}, domain: 'sylament') }}</span>
            <twig:SylamentSupport:IconButton label="{{ 'k'|trans(domain: 'sylament') }}" :iconAlias="constant('Sylament\\Support\\Icons::CLOSE')" />
            {% if position == 'Footer' %}{{ ('sy ' ~ 'sy-x')|trim }}{% endif %}
            TWIG,
        'support/assets/src/controllers/dropdown_controller.ts' => "// Shows \"Select all\" in prose only.\nstatic values = { placement: { type: String, default: \"bottom-start\" } };\nconst TAG_PLACEHOLDER = \":tag\";\nconst COUNT_LABEL = \"__COUNT__\";\n",
    ]);

    try {
        expect(LiteralScan::literals($this::getContainer()->get('twig'), 'support', $directory))->toBe([]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('finds no user-facing literal in any package', function (string $package): void {
    $literals = LiteralScan::literals($this::getContainer()->get('twig'), $package);

    expect($literals)->toBe([], "The {$package} package ships user-facing literals: make each a TranslatableMessage or a |trans key, or, in TypeScript, a Stimulus value its template fills (.claude/rules/php.md, .claude/rules/twig.md, .claude/rules/frontend.md; what the scan reads: docs/internals/i18n.md):\n" . implode("\n", $literals));
})->with(Packages::names());
