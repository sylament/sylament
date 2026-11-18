<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\CheckboxColumn;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A table of two array rows whose columns each carry one piece of a column's chrome: its
 * label, header width, growth and wrapping, tooltips, extra cell and header attributes,
 * responsive visibility, visibility and toggling, links, a click turned off and a disabled
 * checkbox; the cases read each by its column's name.
 */
#[AsLiveComponent(name: 'Tables:ColumnChromeTable', template: '@Fixtures/tables/table.html.twig')]
class ColumnChromeTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                [
                    '__key' => 'lamp',
                    'name' => 'Lamp',
                    'releasedAt' => '2024',
                    'author' => ['name' => 'Ada'],
                    'authorName' => 'Ada',
                    'status_code' => 'A1',
                    'tags' => 'oak,ash',
                    'active' => true,
                    'stocked' => true,
                ],
                [
                    '__key' => 'vase',
                    'name' => 'Vase',
                    'releasedAt' => '2025',
                    'author' => ['name' => 'Grace'],
                    'authorName' => 'Grace',
                    'status_code' => 'B2',
                    'tags' => 'elm',
                    'active' => false,
                    'stocked' => false,
                ],
            ])
            ->columns([
                TextColumn::make('name')
                    ->tooltip(fn (string $state): Markup => new Markup("<b>About</b> {$state}", 'UTF-8'))
                    ->headerTooltip(new Markup('<b>Header</b> tip', 'UTF-8'))
                    ->extraCellAttributes(fn (array $record): array => ['data-row' => $record['name']]),
                TextColumn::make('releasedAt'),
                TextColumn::make('author.name'),
                TextColumn::make('authorName'),
                TextColumn::make('status_code'),
                TextColumn::make('writer')->state('Ada')->label('Written by'),
                TextColumn::make('shelf')->state('Top')->label(new Markup('<em>Shelf</em>', 'UTF-8')),
                TextColumn::make('sku')
                    ->state('S-1')
                    ->width(120)
                    ->grow()
                    ->extraCellAttributes(['data-shelf' => 'top', 'class' => 'shelf-cell'])
                    ->extraCellAttributes(['data-bin' => 'b2'], merge: true)
                    ->extraHeaderAttributes(['data-shelf' => 'top', 'class' => 'shelf-header'])
                    ->extraHeaderAttributes(['data-bin' => 'b2'], merge: true),
                TextColumn::make('note')
                    ->state('Fragile')
                    ->grow()
                    ->hiddenFrom('md')
                    ->extraCellAttributes(['data-first' => 'yes'])
                    ->extraCellAttributes(['data-second' => 'yes'])
                    ->extraHeaderAttributes(['data-first' => 'yes'])
                    ->extraHeaderAttributes(['data-second' => 'yes']),
                TextColumn::make('summary')->state('Brass')->wrapHeader()->visibleFrom('lg'),
                TextColumn::make('missing')->emptyTooltip(new Markup('<i>Nothing</i> here', 'UTF-8')),
                TextColumn::make('hidden')->state('x')->hidden(),
                TextColumn::make('invisible')->state('x')->visible(false),
                TextColumn::make('hiddenHere')->state('x')->hiddenOn(self::class),
                TextColumn::make('visibleElsewhere')->state('x')->visibleOn(ProductTable::class),
                TextColumn::make('visibleHere')->state('x')->visibleOn([ProductTable::class, self::class]),
                TextColumn::make('overruled')->state('x')->hidden()->visible(),
                TextColumn::make('toggleable')->state('x')->toggleable(),
                TextColumn::make('toggledOff')->state('x')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hiddenToggleable')->state('x')->hidden()->toggleable(),
                TextColumn::make('link')->state('Top shelf')->url('/shelves/top'),
                TextColumn::make('newTab')->state('New shelf')->url('/shelves/new', true),
                TextColumn::make('recordLink')->state('Item')->url($this->itemUrl(...)),
                TextColumn::make('tags')->separator()->listWithLineBreaks()->url(fn (string $state): string => "/tags/{$state}"),
                TextColumn::make('renamed')->state(['oak', 'ash'])->listWithLineBreaks()->url(fn (?string $value): string => "/tags/{$value}"),
                TextColumn::make('unsafe')->state('Run')->url('javascript:alert(1)'),
                TextColumn::make('unsafeItems')->state(['oak', 'ash'])->listWithLineBreaks()->url(fn (string $state): string => $state === 'ash' ? 'javascript:alert(1)' : "/tags/{$state}"),
                TextColumn::make('unclickable')->state('Locked shelf')->url('/shelves/locked')->disabledClick(),
                CheckboxColumn::make('active')->disabled(),
                CheckboxColumn::make('stocked'),
            ]);
    }

    /**
     * @param array{__key: string} $record
     */
    private function itemUrl(array $record): string
    {
        return "/items/{$record['__key']}";
    }
}
