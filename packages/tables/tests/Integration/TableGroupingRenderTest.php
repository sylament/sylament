<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Enums\RecordActionsPosition;
use Sylament\Tables\Enums\RecordCheckboxPosition;
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Records\Artist;
use Sylament\Tables\Tests\Fixtures\Records\Song;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Tables\Tests\renderToHtml;

/*
 * Grouping as the partial renders it: the group header row opened at each
 * boundary, the heading, description and collapse control it carries, the group
 * checkbox that ticks a whole group, the stripe restarting inside each group and
 * the two arms `selectGroupsOnly()` removes. The suppression while reordering is
 * asserted as an off/on pair in `TableReorderingTest`, where every other mode
 * gate of this partial is pinned.
 *
 * A record row is counted as `tbody tr[id*=".table.records."]`, never as
 * `tbody .sy-ta-row` — the group header row carries that class too, as vendor's
 * does.
 */

/**
 * A six-row grouped table — three songs on Radiohead, three on Björk, in group
 * order, because the records lane orders nothing itself. The host's own property
 * carries the grouping, which is where the selector writes it from the client.
 *
 * @param list<string> $selectedKeys
 */
function groupedTableHtml(?string $grouping = 'artist.name', ?Closure $configure = null, array $selectedKeys = []): string
{
    $radiohead = new Artist('Radiohead');
    $bjork = new Artist('Björk');

    $host = new TableHost(fn (Table $table): Table => ($configure ?? static fn (Table $table): Table => $table)(
        $table
            ->records(fn (): array => [
                new Song('Airbag', 'OK Computer', $radiohead, id: 1),
                new Song('Let Down', 'OK Computer', $radiohead, id: 2),
                new Song('Creep', 'Pablo Honey', $radiohead, id: 3),
                new Song('Hyperballad', 'Post', $bjork, id: 4),
                new Song('Army of Me', 'Post', $bjork, id: 5),
                new Song('Jóga', 'Homogenic', $bjork, id: 6),
            ])
            ->columns([TextColumn::make('title'), TextColumn::make('album')])
            ->groups(['artist.name', 'album']),
    ));

    $host->tableGrouping = $grouping;
    $host->selectedTableRecordKeys = $selectedKeys;

    return renderToHtml($host->getTable());
}

/**
 * The same table with one artist missing, so the null key's group renders.
 */
function unknownArtistTableHtml(): string
{
    $radiohead = new Artist('Radiohead');

    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [
            new Song('Airbag', 'OK Computer', $radiohead, id: 1),
            new Song('Unattributed', 'Bootleg', id: 2),
        ])
        ->columns([TextColumn::make('title')])
        ->groups(['artist.name'])
        ->bulkActions([BulkAction::make('archive')]));

    $host->tableGrouping = 'artist.name';

    return renderToHtml($host->getTable());
}

it('opens a group header row at each boundary and nowhere else', function (): void {
    $crawler = new Crawler(groupedTableHtml());
    $rows = $crawler->filter('tbody > tr');

    expect($crawler->filter('tr.sy-ta-group-header-row')->count())->toBe(2)
        ->and($crawler->filter('tbody tr[id*=".table.records."]')->count())->toBe(6)
        ->and($rows->eq(0)->attr('class'))->toContain('sy-ta-group-header-row')
        ->and($rows->eq(0)->filter('.sy-ta-group-heading')->text())->toBe('Artist: Radiohead')
        ->and($rows->eq(4)->attr('class'))->toContain('sy-ta-group-header-row')
        ->and($rows->eq(4)->filter('.sy-ta-group-heading')->text())->toBe('Artist: Björk');
});

it('renders no group header rows for a table with no grouping resolved', function (): void {
    $crawler = new Crawler(groupedTableHtml(grouping: null));

    expect($crawler->filter('tr.sy-ta-group-header-row')->count())->toBe(0)
        ->and($crawler->filter('tbody tr[id*=".table.records."]')->count())->toBe(6)
        ->and($crawler->filter('tr[data-sy-group]')->count())->toBe(0);
});

it('carries the group title on the header row and on every record row', function (): void {
    $crawler = new Crawler(groupedTableHtml());
    $header = $crawler->filter('tr.sy-ta-group-header-row')->first();

    expect($header->attr('data-sy-group'))->toBe('Radiohead')
        ->and($header->attr('id'))->toEndWith('.table.groups.Radiohead')
        ->and($crawler->filter('tr[data-sy-group="Radiohead"]')->count())->toBe(4)
        ->and($crawler->filter('tr[data-sy-group="Björk"]')->count())->toBe(4);
});

it('keys the blank group with an empty title on both row kinds', function (): void {
    $crawler = new Crawler(unknownArtistTableHtml());
    $header = $crawler->filter('tr.sy-ta-group-header-row')->last();

    expect($crawler->filter('tr.sy-ta-group-header-row')->count())->toBe(2)
        ->and($header->attr('data-sy-group'))->toBe('')
        ->and($header->attr('id'))->toEndWith('.table.groups.')
        ->and($header->filter('.sy-ta-group-heading')->text())->toBe('Artist:')
        ->and($crawler->filter('tr[data-sy-group=""]')->count())->toBe(2);
});

it('spans the group header row across every cell the head emits', function (
    ?RecordActionsPosition $actionsPosition,
    ?RecordCheckboxPosition $checkboxPosition,
): void {
    $crawler = new Crawler(groupedTableHtml(configure: function (Table $table) use ($actionsPosition, $checkboxPosition): Table {
        if ($actionsPosition instanceof RecordActionsPosition) {
            $table->recordActions([Action::make('edit')], position: $actionsPosition);
        }

        return $checkboxPosition instanceof RecordCheckboxPosition
            ? $table->bulkActions([BulkAction::make('archive')])->recordCheckboxPosition($checkboxPosition)
            : $table->selectable(false);
    }));

    $header = $crawler->filter('tr.sy-ta-group-header-row')->first();
    $colspan = (int) $header->filter('td.sy-ta-group-header-cell')->attr('colspan');

    expect($colspan + $header->filter('td')->count() - 1)->toBe($crawler->filter('thead > tr > th')->count());
})->with([
    'no actions, no selection' => [null, null],
    'no actions, leading checkbox' => [null, RecordCheckboxPosition::BeforeCells],
    'no actions, trailing checkbox' => [null, RecordCheckboxPosition::AfterCells],
    'leading actions, no selection' => [RecordActionsPosition::BeforeCells, null],
    'leading actions, leading checkbox' => [RecordActionsPosition::BeforeCells, RecordCheckboxPosition::BeforeCells],
    'leading actions, trailing checkbox' => [RecordActionsPosition::BeforeCells, RecordCheckboxPosition::AfterCells],
    'actions before the columns, leading checkbox' => [RecordActionsPosition::BeforeColumns, RecordCheckboxPosition::BeforeCells],
    'actions after the columns, leading checkbox' => [RecordActionsPosition::AfterColumns, RecordCheckboxPosition::BeforeCells],
    'trailing actions, leading checkbox' => [RecordActionsPosition::AfterCells, RecordCheckboxPosition::BeforeCells],
    'trailing actions, trailing checkbox' => [RecordActionsPosition::AfterCells, RecordCheckboxPosition::AfterCells],
]);

it('prefixes the heading with the group label until the group refuses it', function (): void {
    $bare = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->groups([Group::make('artist.name')->titlePrefixedWithLabel(false)])));

    expect(new Crawler(groupedTableHtml())->filter('.sy-ta-group-heading')->first()->text())->toBe('Artist: Radiohead')
        ->and($bare->filter('.sy-ta-group-heading')->first()->text())->toBe('Radiohead');
});

it('renders a group description under the heading only when one resolves', function (): void {
    $described = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->groups([Group::make('artist.name')->getDescriptionFromRecordUsing(fn (Song $record): string => $record->album)])));

    expect($described->filter('.sy-ta-group-header p.sy-ta-group-description')->count())->toBe(2)
        ->and($described->filter('.sy-ta-group-description')->first()->text())->toBe('OK Computer')
        ->and(new Crawler(groupedTableHtml())->filter('.sy-ta-group-description')->count())->toBe(0);
});

it('renders a collapse control only for a collapsible group', function (): void {
    $collapsible = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->groups([Group::make('artist.name')->collapsible()])));

    $header = $collapsible->filter('.sy-ta-group-header')->first();
    $button = $header->filter('button.sy-icon-btn.sy-size-sm');

    expect($header->attr('class'))->toContain('sy-collapsible')
        ->and($header->attr('data-action'))->toBe('click->sylament--tables--table#toggleGroup')
        ->and($button->count())->toBe(1)
        ->and($button->attr('type'))->toBe('button')
        ->and($button->attr('aria-expanded'))->toBe('true')
        ->and($button->attr('aria-label'))->toBe('Artist: Radiohead')
        ->and($button->attr('data-action'))->toContain('click->sylament--tables--table#toggleGroup')
        ->and($button->filter('svg.sy-icon')->count())->toBe(1);

    $plain = new Crawler(groupedTableHtml())->filter('.sy-ta-group-header')->first();

    expect($plain->attr('class'))->not->toContain('sy-collapsible')
        ->and($plain->attr('data-action'))->toBeNull()
        ->and($plain->filter('button')->count())->toBe(0);
});

it('paints every group collapsed on the first render under collapsedGroupsByDefault()', function (): void {
    $crawler = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->groups([Group::make('artist.name')->collapsible()])
        ->collapsedGroupsByDefault()));

    expect($crawler->filter('.sy-ta-group-header.sy-collapsed')->count())->toBe(2)
        ->and($crawler->filter('tr.sy-ta-group-header-row.sy-collapsed')->count())->toBe(0)
        ->and($crawler->filter('tbody tr[id*=".table.records."].sy-collapsed')->count())->toBe(6)
        ->and($crawler->filter('.sy-ta-group-header button')->first()->attr('aria-expanded'))->toBe('false')
        ->and(new Crawler(groupedTableHtml())->filter('.sy-collapsed')->count())->toBe(0);
});

it('leaves a non-collapsible group expanded under collapsedGroupsByDefault()', function (): void {
    $crawler = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table->collapsedGroupsByDefault()));

    expect($crawler->filter('.sy-collapsed')->count())->toBe(0);
});

it('restarts the record striping at each group boundary', function (): void {
    $crawler = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table->striped()));
    $striped = $crawler->filter('tbody tr[id*=".table.records."]')
        ->each(fn (Crawler $row): bool => str_contains($row->attr('class') ?? '', 'sy-striped'));

    expect($striped)->toBe([false, true, false, false, true, false]);
});

it('ticks a whole group through one checkbox carrying its keys', function (): void {
    $crawler = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->bulkActions([BulkAction::make('archive')])));

    $checkboxes = $crawler->filter('tr.sy-ta-group-header-row > td.sy-ta-cell.sy-ta-group-selection-cell > input.sy-checkbox.sy-ta-group-checkbox');

    expect($checkboxes->count())->toBe(2)
        ->and($checkboxes->first()->attr('type'))->toBe('checkbox')
        ->and($checkboxes->first()->attr('data-sylament--tables--table-target'))->toBe('groupCheckbox')
        ->and($checkboxes->first()->attr('data-action'))->toBe('change->sylament--tables--table#toggleGroupRecords')
        ->and(json_decode($checkboxes->first()->attr('data-sy-group-keys') ?? '', true))->toBe(['1', '2', '3'])
        ->and(json_decode($checkboxes->last()->attr('data-sy-group-keys') ?? '', true))->toBe(['4', '5', '6'])
        ->and($checkboxes->first()->attr('aria-label'))->toBe('Select/deselect every record in group Radiohead for bulk actions.');
});

it('gives each group checkbox an id of its own', function (): void {
    $crawler = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->bulkActions([BulkAction::make('archive')])));

    $ids = $crawler->filter('input.sy-ta-group-checkbox')->each(fn (Crawler $box): ?string => $box->attr('id'));

    expect($ids)->toHaveCount(2)
        ->and($ids[0])->toEndWith('.table.groups.Radiohead.checkbox')
        ->and($ids[1])->toEndWith('.table.groups.Björk.checkbox')
        ->and($ids[0])->not->toBe($ids[1]);
});

it('paints a fully selected group checked and a partly selected one unchecked', function (): void {
    $crawler = new Crawler(groupedTableHtml(
        configure: fn (Table $table): Table => $table->bulkActions([BulkAction::make('archive')]),
        selectedKeys: ['1', '2', '3', '4'],
    ));

    $checkboxes = $crawler->filter('input.sy-ta-group-checkbox');

    expect($checkboxes->first()->attr('checked'))->not->toBeNull()
        ->and($checkboxes->last()->attr('checked'))->toBeNull();
});

it('paints a group with no selectable record unchecked', function (): void {
    $crawler = new Crawler(groupedTableHtml(
        configure: fn (Table $table): Table => $table
            ->bulkActions([BulkAction::make('archive')])
            ->checkIfRecordIsSelectableUsing(fn (Song $record): bool => $record->artist?->name === 'Radiohead'),
        selectedKeys: ['1', '2', '3'],
    ));

    $checkboxes = $crawler->filter('input.sy-ta-group-checkbox');

    expect($checkboxes->last()->attr('data-sy-group-keys'))->toBe('[]')
        ->and($checkboxes->last()->attr('checked'))->toBeNull()
        ->and($checkboxes->first()->attr('checked'))->not->toBeNull();
});

it('renders no group checkbox for a table with no bulk actions', function (): void {
    $crawler = new Crawler(groupedTableHtml());

    expect($crawler->filter('input.sy-ta-group-checkbox')->count())->toBe(0)
        ->and($crawler->filter('td.sy-ta-group-selection-cell')->count())->toBe(0)
        ->and($crawler->filter('tr.sy-ta-group-header-row')->count())->toBe(2);
});

it('drops the page checkbox but keeps its cell under selectGroupsOnly()', function (): void {
    $crawler = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->bulkActions([BulkAction::make('archive')])
        ->selectGroupsOnly()));

    expect($crawler->filter('thead th.sy-ta-selection-cell')->count())->toBe(1)
        ->and($crawler->filter('input.sy-ta-page-checkbox')->count())->toBe(0)
        ->and($crawler->filter('tbody input.sy-ta-record-checkbox')->count())->toBe(6)
        ->and($crawler->filter('input.sy-ta-group-checkbox')->count())->toBe(2);
});

it('drops the select-all link but keeps the banner under selectGroupsOnly()', function (): void {
    $crawler = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->toolbarActions([BulkAction::make('archive')])
        ->selectGroupsOnly()));

    $banner = $crawler->filter('div.sy-ta-selection-indicator');

    expect($banner->count())->toBe(1)
        ->and($banner->filter('[data-sylament--tables--table-target="selectAll"]')->count())->toBe(0)
        ->and($banner->filter('[data-sylament--tables--table-target="deselectAll"]')->count())->toBe(1);
});

it('keeps the controller and its values with the page checkbox gone', function (): void {
    $root = new Crawler(groupedTableHtml(configure: fn (Table $table): Table => $table
        ->bulkActions([BulkAction::make('archive')])
        ->selectGroupsOnly()))->filter('div.sy.sy-ta');

    expect($root->attr('data-controller'))->toBe('sylament--tables--table')
        ->and($root->attr('data-sylament--tables--table-can-track-value'))->toBe('true')
        ->and($root->attr('data-sylament--tables--table-page-only-value'))->toBe('true')
        ->and($root->attr('data-sylament--tables--table-all-selectable-count-value'))->toBe('6')
        ->and($root->attr('data-sylament--tables--table-groups-collapsed-by-default-value'))->toBe('false')
        ->and($root->attr('data-sylament--tables--table-count-one-value'))->toBe('1 record selected');
});
