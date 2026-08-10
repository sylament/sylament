<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration\Columns;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Records\Artist;
use Sylament\Tables\Tests\Fixtures\Records\Song;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Tables\Tests\renderToHtml;

/*
 * Drives the shipped `TextColumn` partial through a real table render — the
 * state pipeline (dotted paths, the format hook, a null state, an iterable
 * state) landing inside the cell's own `div.sy.sy-ta-text`, selected through
 * the cell chain so the element carrying the state is the asserted one.
 */

it('renders dotted-path and formatted state into cells', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [new Song('Airbag', 'OK Computer', new Artist('Radiohead'), id: 1)])
        ->columns([
            TextColumn::make('artist.name'),
            TextColumn::make('title')->formatStateUsing(fn (string $state): string => mb_strtoupper($state)),
        ]));

    $cells = new Crawler(renderToHtml($host->getTable()))
        ->filter('td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-text');

    expect($cells->count())->toBe(2)
        ->and($cells->eq(0)->text())->toBe('Radiohead')
        ->and($cells->eq(1)->text())->toBe('AIRBAG');
});

it('renders an empty text element for null state', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['note' => null]])
        ->columns([TextColumn::make('note')]));

    $html = renderToHtml($host->getTable());

    expect($html)->toContain('<div class="sy sy-ta-text"></div>');
});

it('joins an iterable state into one comma-separated cell', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['tags' => ['symfony', 'forms']]])
        ->columns([TextColumn::make('tags')]));

    $cells = new Crawler(renderToHtml($host->getTable()))
        ->filter('td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-text');

    expect($cells->eq(0)->text())->toBe('symfony, forms');
});

it('renders a badge per value with colour classes when badged', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['tags' => ['symfony', 'forms']]])
        ->columns([TextColumn::make('tags')->badge()->color('success')]));

    $badges = new Crawler(renderToHtml($host->getTable()))
        ->filter('div.sy-ta-text > span.sy-ta-text-item > span.sy-badge');

    expect($badges->count())->toBe(2)
        ->and($badges->eq(0)->text())->toBe('symfony')
        ->and($badges->eq(1)->text())->toBe('forms')
        ->and($badges->eq(0)->attr('class'))->toContain('sy-color-success');
});

it('renders descriptions above and below the item', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['title' => 'Airbag']])
        ->columns([
            TextColumn::make('title')
                ->description('OK Computer', position: 'above')
                ->description('1997'),
        ]));

    $cell = new Crawler(renderToHtml($host->getTable()))->filter('div.sy-ta-text');
    $children = $cell->children();

    expect($cell->attr('class'))->toContain('sy-ta-text-has-descriptions')
        ->and($children->eq(0)->text())->toBe('OK Computer')
        ->and($children->eq(1)->text())->toBe('Airbag')
        ->and($children->eq(2)->text())->toBe('1997');
});

it('renders size, weight, font family, wrap and line clamp', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['title' => 'Airbag']])
        ->columns([
            TextColumn::make('title')
                ->size('lg')
                ->weight('bold')
                ->fontFamily('mono')
                ->wrap()
                ->lineClamp(2),
        ]));

    $html = renderToHtml($host->getTable());
    $item = new Crawler($html)->filter('div.sy-ta-text > span.sy-ta-text-item');

    expect(new Crawler($html)->filter('div.sy-ta-text')->attr('class'))->toContain('sy-wrapped')
        ->and($item->attr('class'))->toContain('sy-size-lg')
        ->and($item->attr('class'))->toContain('sy-font-bold')
        ->and($item->attr('class'))->toContain('sy-font-mono')
        ->and($item->attr('style'))->toContain('--sy-line-clamp: 2');
});

it('renders an icon before or after the content', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['title' => 'Airbag']])
        ->columns([TextColumn::make('title')->icon('heroicons:x-mark')->iconPosition('after')]));

    $item = new Crawler(renderToHtml($host->getTable()))->filter('div.sy-ta-text > span.sy-ta-text-item');

    expect($item->children()->last()->nodeName())->toBe('svg');
});
