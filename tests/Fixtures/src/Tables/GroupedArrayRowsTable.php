<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Forms\DeliveryMode;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Three array rows from a `records()` closure, `plum` and `fig` in the north zone and
 * `quince` in the south, listed in that order, or with `quince` between the other two
 * under `outOfOrder`. A user groups them by zone, by delivery mode (a backed enum that
 * labels itself) or by code, a group each row's zone titles.
 */
#[AsLiveComponent(name: 'Tables:GroupedArrayRowsTable', template: '@Fixtures/tables/table.html.twig')]
class GroupedArrayRowsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public bool $outOfOrder = false;

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $plum = ['__key' => 'plum', 'label' => 'Plum', 'zone' => 'North', 'mode' => DeliveryMode::Standard, 'code' => 'N1'];
                $fig = ['__key' => 'fig', 'label' => 'Fig', 'zone' => 'North', 'mode' => DeliveryMode::Standard, 'code' => 'N2'];
                $quince = ['__key' => 'quince', 'label' => 'Quince', 'zone' => 'South', 'mode' => DeliveryMode::Courier, 'code' => 'S1'];

                return $this->outOfOrder ? [$plum, $quince, $fig] : [$plum, $fig, $quince];
            })
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('zone'),
            ])
            ->groups([
                'zone',
                'mode',
                Group::make('code')->getTitleFromRecordUsing(static fn (array $record): mixed => $record['zone']),
            ]);
    }
}
