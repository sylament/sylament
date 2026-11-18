<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;

it("formats a money cell in the table's default currency, US dollars until one is set", function (): void {
    $table = Table::make(new class implements InteractsWithTable {
        use HasTable;
    })->defaultNumberLocale('en_US');
    $column = TextColumn::make('price')->money()->table($table)->record(['price' => 1234.5]);

    expect($this->renderViewComponent($column))->toContainElement(TablesSelectors::TEXT_ITEM, '$1,234.50')
        ->and($this->renderViewComponent($column->table($table->defaultCurrency('eur'))))->toContainElement(TablesSelectors::TEXT_ITEM, '€1,234.50');
});

it('formats a date, date-time and time cell by the default display formats', function (): void {
    $table = Table::make(new class implements InteractsWithTable {
        use HasTable;
    });
    $record = ['published' => '2024-01-05 13:04:05'];

    expect($this->renderViewComponent(TextColumn::make('published')->date()->table($table)->record($record)))->toContainElement(TablesSelectors::TEXT_ITEM, 'Jan 5, 2024')
        ->and($this->renderViewComponent(TextColumn::make('published')->dateTime()->table($table)->record($record)))->toContainElement(TablesSelectors::TEXT_ITEM, 'Jan 5, 2024 13:04:05')
        ->and($this->renderViewComponent(TextColumn::make('published')->time()->table($table)->record($record)))->toContainElement(TablesSelectors::TEXT_ITEM, '13:04:05');
});

it('formats a date, date-time and time cell by the display formats the table sets', function (): void {
    $table = Table::make(new class implements InteractsWithTable {
        use HasTable;
    })->defaultDateDisplayFormat('Y-m-d')->defaultDateTimeDisplayFormat('Y-m-d H:i')->defaultTimeDisplayFormat('g:i A');
    $record = ['published' => '2024-01-05 13:04:05'];

    expect($this->renderViewComponent(TextColumn::make('published')->date()->table($table)->record($record)))->toContainElement(TablesSelectors::TEXT_ITEM, '2024-01-05')
        ->and($this->renderViewComponent(TextColumn::make('published')->dateTime()->table($table)->record($record)))->toContainElement(TablesSelectors::TEXT_ITEM, '2024-01-05 13:04')
        ->and($this->renderViewComponent(TextColumn::make('published')->time()->table($table)->record($record)))->toContainElement(TablesSelectors::TEXT_ITEM, '1:04 PM');
});

it("formats numbers in the table's number locale, and in the process locale until one is set", function (): void {
    $processLocale = Locale::getDefault();
    Locale::setDefault('de_DE');

    try {
        $table = Table::make(new class implements InteractsWithTable {
            use HasTable;
        });
        $column = TextColumn::make('plays')->numeric()->table($table)->record(['plays' => 1234.5]);

        expect($this->renderViewComponent($column))->toContainElement(TablesSelectors::TEXT_ITEM, '1.234,5')
            ->and($this->renderViewComponent($column->table($table->defaultNumberLocale('en_US'))))->toContainElement(TablesSelectors::TEXT_ITEM, '1,234.5');
    } finally {
        Locale::setDefault($processLocale);
    }
});
