<?php

declare(strict_types=1);

it('renders a label around the magnifying glass and a search input named "Search", bound to the table search after half a second', function (): void {
    $crawler = $this->renderTwigComponent('SylamentTables:SearchField')->crawler();
    $input = $crawler->filter('label.sy-ta-search-field input[type="search"][aria-label="Search"]');

    expect($crawler)->toContainElement('label.sy-ta-search-field svg title', 'magnifying-glass-20-solid')
        ->and($input)->toHaveCount(1)
        ->and($input->attr('placeholder'))->toBe('Search')
        ->and($input->attr('autocomplete'))->toBe('off')
        ->and($input->attr('maxlength'))->toBe('1000')
        ->and($input->attr('data-model'))->toBe('debounce(500)|tableSearch')
        ->and($input->attr('id'))->toBeNull()
        ->and($input->attr('value'))->toBeNull();
});

it('names, fills, keys and binds its input as it is told, committing on change rather than after a pause when searching on blur', function (bool $onBlur, string $dataModel): void {
    $input = $this->renderTwigComponent('SylamentTables:SearchField', [
        'id' => 'larders.table.column-search.name.input',
        'label' => 'Search Name',
        'placeholder' => 'Find a larder',
        'model' => 'tableColumnSearches.name',
        'value' => 'Lamp',
        'onBlur' => $onBlur,
        'debounceMs' => 1000,
    ])->crawler()->filter('input[type="search"][aria-label="Search Name"]');

    expect($input->attr('id'))->toBe('larders.table.column-search.name.input')
        ->and($input->attr('placeholder'))->toBe('Find a larder')
        ->and($input->attr('value'))->toBe('Lamp')
        ->and($input->attr('data-model'))->toBe($dataModel);
})->with([
    'after a pause' => [false, 'debounce(1000)|tableColumnSearches.name'],
    'on blur' => [true, 'on(change)|tableColumnSearches.name'],
]);
