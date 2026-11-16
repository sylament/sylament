<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\State\ViolationPathTranslator;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\SubtreeOwningGroup;

it("joins the schema's own path to the record path a violation names, and answers the record path alone under a schema without one", function (): void {
    $map = ['title' => TextInput::make('title')];

    expect(new ViolationPathTranslator($map, 'data')->translate('title'))->toBe('data.title')
        ->and(new ViolationPathTranslator($map, null)->translate('title'))->toBe('title')
        ->and(new ViolationPathTranslator($map, '')->translate('title'))->toBe('title');
});

describe('translate()', function (): void {
    it('answers the deepest declared record path covering the violation, folding one nothing beneath an owner declares onto the owner', function (): void {
        $translator = new ViolationPathTranslator([
            'author' => SubtreeOwningGroup::make([TextInput::make('name')])->statePath('author'),
            'author.name' => TextInput::make('name'),
        ], 'data');

        expect($translator->translate('author.name'))->toBe('data.author.name')
            ->and($translator->translate('author.name.first'))->toBe('data.author.name')
            ->and($translator->translate('author.email'))->toBe('data.author');
    });

    it('answers null for a violation no declared path owns', function (string $violationPath): void {
        $translator = new ViolationPathTranslator(['title' => TextInput::make('title')], 'data');

        expect($translator->translate($violationPath))->toBeNull()
            ->and($translator->translateToErrorPath($violationPath))->toBeNull()
            ->and($translator->translate('title'))->toBe('data.title');
    })->with([
        "a class-level constraint's empty path" => [''],
        'a property no component maps' => ['slug'],
        'a path the property path parser refuses' => ['title['],
    ]);
});

describe('translateToErrorPath()', function (): void {
    it('answers the owning path for a component that shows its own errors, and null for a layout component entangling a related record', function (Closure $component, ?string $expected): void {
        $translator = new ViolationPathTranslator(['plaque' => $component()], 'data');

        expect($translator->translate('plaque'))->toBe('data.plaque')
            ->and($translator->translateToErrorPath('plaque'))->toBe($expected);
    })->with([
        'a field' => [fn (): Component => TextInput::make('plaque'), 'data.plaque'],
        'a group with a relationship' => [fn (): Component => Group::make([TextInput::make('title')])->relationship('plaque'), null],
    ]);
});

describe('a component keying items', function (): void {
    beforeEach(function (): void {
        $host = new RecordMappingHost();
        $host->mapping(Repeater::make('sections')->components([
            TextInput::make('heading'),
            Group::make([TextInput::make('locale')])->statePath('meta'),
            Repeater::make('tags')->components([TextInput::make('name')]),
            Group::make([TextInput::make('title')])->relationship('plaque'),
        ]));
        $host->data = ['sections' => ['record-7' => ['heading' => 'Intro'], 'record-9' => ['heading' => 'Outro']]];

        $this->translator = new ViolationPathTranslator(['sections' => $host->getSchemaComponent('schema.sections')], 'data');
    });

    it('names the item the index keys, and folds a violation on the collection or on an index naming no item onto the component', function (): void {
        expect($this->translator->translate('sections[1].heading'))->toBe('data.sections.record-9.heading')
            ->and($this->translator->translate('sections'))->toBe('data.sections')
            ->and($this->translator->translateToErrorPath('sections'))->toBe('data.sections')
            ->and($this->translator->translate('sections[5].heading'))->toBe('data.sections')
            ->and($this->translator->translateToErrorPath('sections[5].heading'))->toBe('data.sections');
    });

    it('folds a violation inside an item onto the deepest path the item declares, filing one that stops at the item nowhere of its own', function (string $violationPath, string $statePath, ?string $errorPath): void {
        expect($this->translator->translate($violationPath))->toBe($statePath)
            ->and($this->translator->translateToErrorPath($violationPath))->toBe($errorPath);
    })->with([
        'a declared item field' => ['sections[0].heading', 'data.sections.record-7.heading', 'data.sections.record-7.heading'],
        'a path beneath a declared item field' => ['sections[0].meta.locale.code', 'data.sections.record-7.meta.locale', 'data.sections.record-7.meta.locale'],
        'an item property no item field maps' => ['sections[0].internalNote', 'data.sections.record-7', null],
        "an item's class-level constraint" => ['sections[0]', 'data.sections.record-7', null],
        "a nested repeater's item" => ['sections[0].tags[1].name', 'data.sections.record-7.tags', 'data.sections.record-7.tags'],
        'a group with a relationship inside an item' => ['sections[0].plaque', 'data.sections.record-7.plaque', 'data.sections.record-7.plaque'],
    ]);
});
