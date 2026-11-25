<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use LogicException;
use Sylament\Tests\Architecture\Translations\CatalogueScan;
use Sylament\Tests\Architecture\Translations\Packages;
use Sylament\Tests\Architecture\Translations\PlantedPackages;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

it('reports a key the code uses and the catalogue lacks', function (): void {
    $directory = PlantedPackages::plant([
        'support/src/Greeting.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('greeting.label', [], 'sylament');\n\$b = new TranslatableMessage('held.label', [], 'sylament');\n",
        'support/templates/components/Greeting/index.html.twig' => "{{ 'components.greeting.heading'|trans(domain: 'sylament') }}",
        'support/translations/sylament+intl-icu.en.yaml' => PlantedPackages::catalogue(['held.label']),
    ]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'support', $directory))->toBe([
            'missing sylament: components.greeting.heading',
            'missing sylament: greeting.label',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a catalogue key nothing uses', function (): void {
    $directory = PlantedPackages::plant([
        'support/src/Greeting.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('used.label', [], 'sylament');\n",
        'support/translations/sylament+intl-icu.en.yaml' => PlantedPackages::catalogue(['dependent.label', 'stale.label', 'used.label']),
        'tables/src/Column.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('dependent.label', [], 'sylament');\n",
    ], requires: ['tables' => ['support']]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'support', $directory))->toBe(['unused sylament: stale.label']);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a catalogue key outside the key shape', function (): void {
    $directory = PlantedPackages::plant([
        'support/templates/components/Greeting/index.html.twig' => "{{ 'components/greeting.label'|trans(domain: 'sylament') }}{{ 'global-search.label'|trans(domain: 'sylament') }}{{ 'Greeting.label'|trans(domain: 'sylament') }}{{ 'components.greeting.held_label'|trans(domain: 'sylament') }}",
        'support/translations/sylament+intl-icu.en.yaml' => PlantedPackages::catalogue(['components/greeting.label', 'global-search.label', 'Greeting.label', 'components.greeting.held_label']),
    ]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'support', $directory))->toBe([
            'malformed sylament: Greeting.label',
            'malformed sylament: components/greeting.label',
            'malformed sylament: global-search.label',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a domain the package may not name', function (): void {
    $directory = PlantedPackages::plant([
        'support/translations/sylament+intl-icu.en.yaml' => PlantedPackages::catalogue(['components.copyable.messages.copied']),
        'tables/src/Column.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('components.copyable.messages.copied', [], 'sylament');\n\$b = new TranslatableMessage('resources.pages.list_records.title', [], 'sylament-panels');\n",
        'tables/templates/partials/Table/index.html.twig' => "{{ 'table.empty.heading'|trans }}",
        'panels/translations/sylament-panels+intl-icu.en.yaml' => PlantedPackages::catalogue(['resources.pages.list_records.title']),
    ], requires: ['tables' => ['support']]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'tables', $directory))->toBe([
            'forbidden messages: table.empty.heading',
            'forbidden sylament-panels: resources.pages.list_records.title',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a catalogue of a domain the package does not own', function (): void {
    $directory = PlantedPackages::plant([
        'support/translations/sylament-forms+intl-icu.en.yaml' => PlantedPackages::catalogue(['components.select.placeholder']),
    ]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'support', $directory))->toBe(['misplaced sylament-forms: translations/sylament-forms+intl-icu.en.yaml']);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('refuses a catalogue named in neither shape', function (string $file): void {
    $directory = PlantedPackages::plant([
        'forms/translations/' . $file => PlantedPackages::catalogue(['components.select.placeholder']),
    ]);

    try {
        expect(fn (): array => CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'forms', $directory))
            ->toThrow(LogicException::class, '/' . $file . ' is not named <domain>+intl-icu.en.yaml or validators.en.yaml');
    } finally {
        PlantedPackages::remove($directory);
    }
})->with([
    'an own-domain catalogue without ICU' => ['sylament-forms.en.yaml'],
    'a validators catalogue with ICU' => ['validators+intl-icu.en.yaml'],
    'an XLIFF catalogue' => ['sylament-forms+intl-icu.en.xlf'],
]);

it('reads every shape a validator message ships in, and reports one validators.en.yaml lacks or no longer ships', function (): void {
    $directory = PlantedPackages::plant([
        'forms/src/Validation/Shapes.php' => <<<'PHP'
            <?php
            final class Shapes extends Constraint
            {
                public string $message = 'A property default.';

                public string $notMessage = 'A held property default.';

                public function __construct(
                    public string $sizeMessage = 'A promoted default.',
                    ?string $message = null,
                    ?string $operator = null,
                ) {
                    parent::__construct(pattern: '/x/', message: $message ?? 'A parent argument.');
                    $this->message = $message ?? match ($operator) {
                        'after' => 'An assigned arm.',
                        default => 'This value should not be blank.',
                    };
                }
            }
            PHP,
        'forms/src/Components/Upload.php' => "<?php\nuse Sylament\\Forms\\Validation\\Prohibited;\n\$context->buildViolation('A violation id.')->addViolation();\n\$context->buildViolation(\$message)->addViolation();\n\$constraint = new Prohibited(message: 'A constraint argument.');\n",
        'forms/translations/validators.en.yaml' => PlantedPackages::catalogue(['A held property default.', 'A stale message.']),
    ]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'forms', $directory))->toBe([
            'missing validators: A constraint argument.',
            'missing validators: A parent argument.',
            'missing validators: A promoted default.',
            'missing validators: A property default.',
            'missing validators: A violation id.',
            'missing validators: An assigned arm.',
            'unused validators: A stale message.',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reads a string constant a violation id or a refusal map names, and only there', function (): void {
    $directory = PlantedPackages::plant([
        'schemas/src/State/Reader.php' => <<<'PHP'
            <?php
            class Reader
            {
                private const string CHANGED = 'A refusal constant.';

                private const string HELD = 'A held refusal constant.';

                private const string VIOLATION = 'A violation constant.';

                private const string ICON = 'heroicons:x-mark';

                public function refusals(string $path): array
                {
                    $refusals[$path] = self::CHANGED;
                    $refusals[$path . '.held'] = static::HELD;
                    $icons[$path] = self::ICON;
                    $context->buildViolation(self::VIOLATION)->addViolation();

                    return $refusals;
                }
            }
            PHP,
        'schemas/translations/validators.en.yaml' => PlantedPackages::catalogue(['A held refusal constant.']),
    ]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'schemas', $directory))->toBe([
            'missing validators: A refusal constant.',
            'missing validators: A violation constant.',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reads a named domain inside a component block', function (): void {
    $directory = PlantedPackages::plant([
        'support/templates/components/Panel/index.html.twig' => "<twig:SylamentSupport:Section>{{ 'k'|trans(domain: 'sylament') }}</twig:SylamentSupport:Section>",
    ]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'support', $directory))->toBe(['missing sylament: k']);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('parses every shipped catalogue', function (): void {
    $this::getContainer();
    $tester = new CommandTester(new Application($this::$kernel)->find('lint:translations'));

    expect($tester->execute(['--locale' => ['en']]))->toBe(0)
        ->and($tester->getDisplay())->toContain('All translations are valid.');
});

it("matches each package's catalogues to the keys its code uses", function (string $package): void {
    $problems = CatalogueScan::problems($this::getContainer()->get('translation.extractor'), $package);

    expect($problems)->toBe([], "The {$package} catalogues differ from the keys its code uses: add a missing key to the catalogue holding its domain, drop an unused one, write each key in the catalogue's shape, and name only a domain the package may (.claude/rules/catalogues.md; what the scan reads: docs/internals/i18n.md):\n" . implode("\n", $problems));
})->with(Packages::names());
