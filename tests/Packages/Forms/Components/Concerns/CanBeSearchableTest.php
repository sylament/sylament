<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('switches the select to the JS control with its search box on, and searchable(false) leaves it native', function (): void {
    $searchable = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->searchable());

    expect($searchable)->toContainElement(Selectors::JS_CONTROL)
        ->and($this->selectControllerValue($searchable, 'searchable'))->toBe('true');

    expect($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->searchable(false)))
        ->toContainElement(Selectors::NATIVE_SELECT)
        ->not->toContainElement(Selectors::JS_CONTROL);
});

it('hands the control its four messages, the defaults until a field sets its own', function (): void {
    $messages = ['noOptionsMessage', 'noSearchResultsMessage', 'searchPrompt', 'searchingMessage'];
    $read = fn (Select $select): array => array_combine($messages, array_map(fn (string $name): mixed => $this->selectControllerValue($this->renderSchemaComponent($select), $name), $messages));

    expect($read(Select::make('plant')->options(['fern' => 'Fern'])->searchable()))->toBe([
        'noOptionsMessage' => 'No options available.',
        'noSearchResultsMessage' => 'No options match your search.',
        'searchPrompt' => 'Start typing to search...',
        'searchingMessage' => 'Searching...',
    ])->and($read(Select::make('plant')->options(['fern' => 'Fern'])->searchable()
        ->noOptionsMessage('Nothing to pick.')
        ->noSearchResultsMessage('No plant matches.')
        ->searchPrompt('Type a plant name')
        ->searchingMessage('Looking...')))->toBe([
            'noOptionsMessage' => 'Nothing to pick.',
            'noSearchResultsMessage' => 'No plant matches.',
            'searchPrompt' => 'Type a plant name',
            'searchingMessage' => 'Looking...',
        ]);
});

it('debounces the search by a second unless told otherwise', function (): void {
    expect($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->searchable()), 'searchDebounce'))->toBe('1000')
        ->and($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->searchable()->searchDebounce(300)), 'searchDebounce'))->toBe('300');
});

it('matches typed text against the option labels by default, and against what the two switches turn on', function (Closure $configure, array $fields): void {
    expect($this->selectControllerValue($this->renderSchemaComponent($configure(Select::make('plant')->options(['fern' => 'Fern'])->searchable())), 'searchFields'))->toBe($fields);
})->with([
    'by default' => [static fn (Select $select): Select => $select, ['label']],
    'values instead' => [static fn (Select $select): Select => $select->searchValues()->searchLabels(false), ['value']],
    'labels switched off by null' => [static fn (Select $select): Select => $select->searchLabels(null), []],
]);
