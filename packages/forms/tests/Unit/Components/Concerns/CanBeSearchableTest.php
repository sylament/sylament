<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;
use Twig\Markup;

it('is not searchable by default', function (): void {
    expect(FixtureOptionsField::make('status')->isSearchable())->toBeFalse();
});

it('turns searching on with a bare call', function (): void {
    expect(FixtureOptionsField::make('status')->searchable()->isSearchable())->toBeTrue()
        ->and(FixtureOptionsField::make('status')->searchable(false)->isSearchable())->toBeFalse();
});

it('reads a null searchable condition as not searchable', function (): void {
    expect(FixtureOptionsField::make('status')->searchable(null)->isSearchable())->toBeFalse();
});

it('evaluates a searchable closure', function (): void {
    expect(FixtureOptionsField::make('status')->searchable(fn (): bool => true)->isSearchable())->toBeTrue();
});

it('defaults the four search messages to their English literals', function (): void {
    $field = FixtureOptionsField::make('status');

    expect($field->getSearchPrompt())->toBe('Start typing to search...')
        ->and($field->getNoOptionsMessage())->toBe('No options available.')
        ->and($field->getNoSearchResultsMessage())->toBe('No options match your search.')
        ->and($field->getSearchingMessage())->toBe('Searching...');
});

it('overrides the search messages', function (): void {
    $markup = new Markup('<b>Nothing here</b>', 'UTF-8');

    $field = FixtureOptionsField::make('status')
        ->searchPrompt('Type a name')
        ->noOptionsMessage($markup)
        ->noSearchResultsMessage(fn (): string => 'Nothing matched')
        ->searchingMessage('Looking...');

    expect($field->getSearchPrompt())->toBe('Type a name')
        ->and($field->getNoOptionsMessage())->toBe($markup)
        ->and($field->getNoSearchResultsMessage())->toBe('Nothing matched')
        ->and($field->getSearchingMessage())->toBe('Looking...');
});

it('falls back to the literal when a message setter is given null', function (): void {
    expect(FixtureOptionsField::make('status')->searchPrompt(null)->getSearchPrompt())
        ->toBe('Start typing to search...');
});

it('debounces the search at a second by default', function (): void {
    expect(FixtureOptionsField::make('status')->getSearchDebounce())->toBe(1000)
        ->and(FixtureOptionsField::make('status')->searchDebounce(250)->getSearchDebounce())->toBe(250)
        ->and(FixtureOptionsField::make('status')->searchDebounce(fn (): int => 50)->getSearchDebounce())->toBe(50);
});

it('searches labels and not values by default', function (): void {
    $field = FixtureOptionsField::make('status');

    expect($field->shouldSearchLabels())->toBeTrue()
        ->and($field->shouldSearchValues())->toBeFalse()
        ->and($field->getSearchableOptionFields())->toBe(['label']);
});

it('composes the searchable option fields from both switches', function (): void {
    expect(FixtureOptionsField::make('status')->searchValues()->getSearchableOptionFields())
        ->toBe(['label', 'value'])
        ->and(FixtureOptionsField::make('status')->searchLabels(false)->searchValues()->getSearchableOptionFields())
        ->toBe(['value'])
        ->and(FixtureOptionsField::make('status')->searchLabels(false)->getSearchableOptionFields())
        ->toBe([]);
});

it('reads a null label or value condition as off', function (): void {
    $field = FixtureOptionsField::make('status')->searchLabels(null)->searchValues(null);

    expect($field->shouldSearchLabels())->toBeFalse()
        ->and($field->shouldSearchValues())->toBeFalse()
        ->and($field->getSearchableOptionFields())->toBe([]);
});

it('evaluates closure label and value conditions', function (): void {
    $field = FixtureOptionsField::make('status')
        ->searchLabels(fn (): bool => false)
        ->searchValues(fn (): bool => true);

    expect($field->shouldSearchLabels())->toBeFalse()
        ->and($field->shouldSearchValues())->toBeTrue()
        ->and($field->getSearchableOptionFields())->toBe(['value']);
});
