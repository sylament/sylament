<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('renders the placeholder as a blank first option until selectablePlaceholder(false) drops it', function (): void {
    $render = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern']));

    expect($render->filter(Selectors::SELECT . ' option')->first()->attr('value'))->toBe('')
        ->and($render->filter(Selectors::SELECT . ' option')->first()->text())->toBe('Select an option');

    expect($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->selectablePlaceholder(false))->filter(Selectors::SELECT . ' option')->each(static fn ($option): ?string => $option->attr('value')))
        ->toBe(['fern']);
});

it('leaves the blank option without text while the select is disabled', function (): void {
    $option = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->disabled())->filter(Selectors::SELECT . ' option[value=""]');

    expect($option->count())->toBe(1)
        ->and($option->text())->toBe('');
});

it('tells the JS control whether the placeholder can be picked', function (): void {
    expect($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)), 'canSelectPlaceholder'))->toBe('true')
        ->and($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)->selectablePlaceholder(false)), 'canSelectPlaceholder'))->toBe('false');
});
