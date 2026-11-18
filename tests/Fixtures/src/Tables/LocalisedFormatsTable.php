<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table setting its own date, date-time and time formats, its currency and, unless
 * `numberLocale` is null, its number locale, with one text column per date, money and
 * numeric arm that reads them.
 */
#[AsLiveComponent(name: 'Tables:LocalisedFormatsTable', template: '@Fixtures/tables/table.html.twig')]
class LocalisedFormatsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public ?string $numberLocale = 'de_DE';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => 'till', 'packedOn' => '2024-03-05 14:30:00', 'packedAt' => '2024-03-05 14:30:00', 'packedTime' => '2024-03-05 14:30:00', 'price' => 1234.5, 'figure' => 1234567.891],
            ])
            ->defaultDateDisplayFormat('d/m/Y')
            ->defaultDateTimeDisplayFormat('d/m/Y H:i')
            ->defaultTimeDisplayFormat('H:i')
            ->defaultCurrency('eur')
            ->defaultNumberLocale($this->numberLocale)
            ->columns([
                TextColumn::make('packedOn')->date()->dateTimeTooltip(),
                TextColumn::make('packedAt')->dateTime()->timeTooltip(),
                TextColumn::make('packedTime')->time()->dateTooltip(),
                TextColumn::make('price')->money(),
                TextColumn::make('figure')->numeric(),
            ]);
    }
}
