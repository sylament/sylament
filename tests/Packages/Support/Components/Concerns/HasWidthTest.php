<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;

it('renders a width given as an integer in pixels', function (): void {
    $crawler = $this->renderSchemaComponent(
        Repeater::make('lines')
            ->reorderable(false)
            ->deletable(false)
            ->table([TableColumn::make('Name')->width(120)])
            ->components([TextInput::make('name')]),
        ['lines' => ['a' => ['name' => 'Ada']]],
    );

    expect($crawler->filter(FormsSelectors::TABLE_REPEATER_HEADER_CELL)->attr('style'))->toBe('width: 120px');
});
