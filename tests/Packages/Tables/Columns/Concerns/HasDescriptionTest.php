<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

use function Symfony\Component\Translation\t;

it('adds its description below its values, or above them, and marks the root as described, but not for a blank one', function (): void {
    $render = fn (TextColumn $column): Crawler => $this->renderViewComponent($column->record(['name' => 'Lamp']));
    $below = $render(TextColumn::make('name')->description('Brass'));
    $above = $render(TextColumn::make('name')->description('Brass', position: 'above'));
    $blank = $render(TextColumn::make('name')->description('   '));

    expect($below)->toContainElement(Selectors::TEXT_COLUMN . '.sy-ta-text-has-descriptions ' . Selectors::TEXT_ITEM . ' + .sy-ta-text-description', 'Brass')
        ->and($above)->toContainElement(Selectors::TEXT_COLUMN . '.sy-ta-text-has-descriptions .sy-ta-text-description', 'Brass')
        ->and($above->filter('.sy-ta-text-description + ' . Selectors::TEXT_ITEM)->text())->toBe('Lamp')
        ->and($blank)->toContainElement(Selectors::TEXT_COLUMN . ':not(.sy-ta-text-has-descriptions)', 'Lamp')
        ->and($blank->filter('.sy-ta-text-description'))->toHaveCount(0);
});

it('places a description below when its position is a closure, since the position is compared rather than evaluated', function (): void {
    $cell = $this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp'])->description('Brass', position: fn (): string => 'above'));

    expect($cell->filter(Selectors::TEXT_ITEM . ' + .sy-ta-text-description')->text())->toBe('Brass')
        ->and($cell->filter('.sy-ta-text-description + ' . Selectors::TEXT_ITEM))->toHaveCount(0);
});

it('adds a description given as a message in its translation', function (): void {
    $cell = $this->renderViewComponent(TextColumn::make('name')->record(['name' => 'Lamp'])->description(t('column.description', [], 'fixtures-tables')));

    expect($cell)->toContainElement(Selectors::TEXT_COLUMN . ' .sy-ta-text-description', 'As written (translated)');
});
