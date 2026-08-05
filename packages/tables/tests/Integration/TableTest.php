<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Records\Song;
use Twig\Markup;

use function Sylament\Tables\Tests\renderToHtml;

/*
 * Drives the shipped `Table` partial: one render exercises the fluent API, the
 * host-side record resolution, the per-cell column binding and the empty-state
 * branch at once. The DOM contract asserted here is what the tables stylesheet
 * and the demo's browser tests target.
 */

it('renders header labels and cell values for object records', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [new Song('Airbag', 'OK Computer'), new Song('Let Down', 'OK Computer')])
        ->columns([TextColumn::make('title'), TextColumn::make('album')]));

    $html = renderToHtml($host->getTable());

    expect($html)
        ->toContain('sy-ta-table')
        ->toContain('<th scope="col" class="sy-ta-header-cell">Title</th>')
        ->toContain('<th scope="col" class="sy-ta-header-cell">Album</th>')
        ->toContain('Airbag')
        ->toContain('Let Down')
        ->not->toContain('sy-ta-empty-state');
});

it('renders one row per record with per-record column binding', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['title' => 'A'], ['title' => 'B'], ['title' => 'C']])
        ->columns([TextColumn::make('title')]));

    expect(mb_substr_count(renderToHtml($host->getTable()), 'sy-ta-row'))->toBe(3);
});

it('renders the default empty state instead of the table', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [])
        ->columns([TextColumn::make('title')]));

    $html = renderToHtml($host->getTable());

    expect($html)
        ->toContain('sy-ta-empty-state')
        ->toContain('No records')
        ->toContain('<svg')
        ->not->toContain('<table')
        ->not->toContain('sy-ta-empty-state-description');
});

it('renders a configured empty state', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [])
        ->emptyStateHeading('No songs yet')
        ->emptyStateDescription('Add the first one.')
        ->emptyStateIcon('heroicons:x-mark'));

    $html = renderToHtml($host->getTable());

    expect($html)
        ->toContain('No songs yet')
        ->toContain('Add the first one.');
});

it('renders a markup empty state icon raw', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [])
        ->emptyStateIcon(new Markup('<svg data-custom-markup></svg>', 'UTF-8')));

    expect(renderToHtml($host->getTable()))
        ->toContain('<span class="sy sy-icon sy-size-lg"><svg data-custom-markup></svg></span>');
});

it('escapes record values', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['title' => '<script>alert(1)</script>']])
        ->columns([TextColumn::make('title')]));

    expect(renderToHtml($host->getTable()))
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert(1)</script>');
});
