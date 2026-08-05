<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration\Columns;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Records\Artist;
use Sylament\Tables\Tests\Fixtures\Records\Song;

use function Sylament\Tables\Tests\renderToHtml;

/*
 * Drives the shipped `TextColumn` partial through a real table render — the
 * state pipeline (dotted paths, the format hook, a null state, an iterable
 * state) landing in the cell markup rather than in a getter's return value.
 */

it('renders dotted-path and formatted state into cells', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [new Song('Airbag', 'OK Computer', new Artist('Radiohead'))])
        ->columns([
            TextColumn::make('artist.name'),
            TextColumn::make('title')->formatStateUsing(fn (string $state): string => mb_strtoupper($state)),
        ]));

    $html = renderToHtml($host->getTable());

    expect($html)->toContain('Radiohead')->toContain('AIRBAG');
});

it('renders an empty text element for null state and joins iterable state', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['tags' => ['symfony', 'forms'], 'note' => null]])
        ->columns([TextColumn::make('tags'), TextColumn::make('note')]));

    $html = renderToHtml($host->getTable());

    expect($html)->toContain('symfony, forms')
        ->toContain('<div class="sy-ta-text"></div>');
});
