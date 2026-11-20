<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Translations\CatalogueScan;
use Sylament\Tests\Architecture\Translations\Packages;
use Sylament\Tests\Architecture\Translations\PlantedPackages;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

it('reports a key the code uses and the catalogue lacks', function (): void {
    $directory = PlantedPackages::plant([
        'support/src/Greeting.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('greeting.label', [], 'sylament');\n\$b = new TranslatableMessage('held.label', [], 'sylament');\n",
        'support/templates/components/Greeting/index.html.twig' => "{{ 'components/greeting.heading'|trans(domain: 'sylament') }}",
        'support/translations/sylament+intl-icu.en.xlf' => PlantedPackages::catalogue(['held.label']),
    ]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'support', $directory))->toBe([
            'missing sylament: components/greeting.heading',
            'missing sylament: greeting.label',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a catalogue key nothing uses', function (): void {
    $directory = PlantedPackages::plant([
        'support/src/Greeting.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('used.label', [], 'sylament');\n",
        'support/translations/sylament+intl-icu.en.xlf' => PlantedPackages::catalogue(['dependent.label', 'stale.label', 'used.label']),
        'tables/src/Column.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('dependent.label', [], 'sylament');\n",
    ], requires: ['tables' => ['support']]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'support', $directory))->toBe(['unused sylament: stale.label']);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reports a domain the package may not name', function (): void {
    $directory = PlantedPackages::plant([
        'support/translations/sylament+intl-icu.en.xlf' => PlantedPackages::catalogue(['components/copyable.messages.copied']),
        'tables/src/Column.php' => "<?php\nuse Symfony\\Component\\Translation\\TranslatableMessage;\n\$a = new TranslatableMessage('components/copyable.messages.copied', [], 'sylament');\n\$b = new TranslatableMessage('resources/pages/list-records.title', [], 'sylament-panels');\n",
        'tables/templates/partials/Table/index.html.twig' => "{{ 'table.empty.heading'|trans }}",
        'panels/translations/sylament-panels+intl-icu.en.xlf' => PlantedPackages::catalogue(['resources/pages/list-records.title']),
    ], requires: ['tables' => ['support']]);

    try {
        expect(CatalogueScan::problems($this::getContainer()->get('translation.extractor'), 'tables', $directory))->toBe([
            'forbidden messages: table.empty.heading',
            'forbidden sylament-panels: resources/pages/list-records.title',
        ]);
    } finally {
        PlantedPackages::remove($directory);
    }
});

it('reads every shape a validator message ships in, and reports one validators.en.xlf lacks or no longer ships', function (): void {
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
        'forms/translations/validators.en.xlf' => PlantedPackages::catalogue(['A held property default.', 'A stale message.']),
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

it("keeps each package's catalogue problems to its baseline", function (string $package): void {
    $problems = CatalogueScan::problems($this::getContainer()->get('translation.extractor'), $package);

    expect($problems)->toBe(CatalogueScan::baseline($package), "The {$package} catalogues differ from tests/Architecture/Translations/catalogue-baseline/{$package}.txt: add the missing key, drop the unused one, or delete the baseline line you fixed (.claude/rules/catalogues.md):\n" . implode("\n", $problems));
})->with(Packages::names());
