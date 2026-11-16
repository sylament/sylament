<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\OperationForm;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('hidden and visible', function (): void {
    it('leaves out of the render a component hidden, or not visible', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Notes')->components([
            Text::make('Shown'),
            Text::make('Hidden')->hidden(),
            Text::make('Not visible')->visible(false),
        ]));

        expect($render)
            ->toContainElement(Selectors::TEXT, 'Shown')
            ->and($render->filter(Selectors::TEXT)->count())->toBe(1);
    });

    it('stays hidden when told both hidden and visible', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Notes')->components([
            Text::make('Shown'),
            Text::make('Both')->hidden()->visible(),
        ]));

        expect($render)
            ->toContainElement(Selectors::TEXT, 'Shown')
            ->and($render->filter(Selectors::TEXT)->count())->toBe(1);
    });
});

describe('hiddenWhenAllChildComponentsHidden', function (): void {
    it('hides a container whose children are all hidden, and keeps one with a visible child', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Outer')->components([
            Section::make('Emptied')->hiddenWhenAllChildComponentsHidden()->components([
                Text::make('a')->hidden(),
                Text::make('b')->hidden(),
            ]),
            Section::make('Kept')->hiddenWhenAllChildComponentsHidden()->components([
                Text::make('c')->hidden(),
                Text::make('Visible'),
            ]),
        ]));

        expect($render->filter('h3')->each(static fn (Crawler $heading): string => $heading->text()))->toBe(['Kept'])
            ->and($render)->toContainElement(Selectors::TEXT, 'Visible');
    });
});

describe('on operations', function (): void {
    it('hides a component on the operation it names', function (string $field): void {
        expect($this->createLiveComponent(OperationForm::class, ['operation' => 'create'])->render()->crawler())
            ->toContainElement("input[name=\"{$field}\"]")
            ->and($this->createLiveComponent(OperationForm::class, ['operation' => 'edit'])->render()->crawler())
            ->toContainElement('input[name="hiddenByUnknown"]')
            ->not->toContainElement("input[name=\"{$field}\"]");
    })->with([
        'a string' => 'hiddenByString',
        'an Operation case' => 'hiddenByCase',
        'a list' => 'hiddenByList',
    ]);

    it('hides a component on its host class under every operation, and hides nothing on a name that is neither', function (): void {
        foreach (['create', 'edit'] as $operation) {
            expect($this->createLiveComponent(OperationForm::class, ['operation' => $operation])->render()->crawler())
                ->toContainElement('input[name="hiddenByUnknown"]')
                ->not->toContainElement('input[name="hiddenByHost"]');
        }
    });

    it('shows a component only on the operation it names', function (): void {
        expect($this->createLiveComponent(OperationForm::class, ['operation' => 'edit'])->render()->crawler())
            ->toContainElement('input[name="visibleByCase"]')
            ->and($this->createLiveComponent(OperationForm::class, ['operation' => 'create'])->render()->crawler())
            ->toContainElement('input[name="hiddenByUnknown"]')
            ->not->toContainElement('input[name="visibleByCase"]');
    });
});
