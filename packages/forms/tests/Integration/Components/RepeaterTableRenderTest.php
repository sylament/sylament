<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\Toggle;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\VerticalAlignment;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

use function Sylament\Forms\Tests\renderRepeater;

/*
 * Table mode through the real Twig stack: the columns are paired with the item's
 * components by position, so every assertion here is about what that pairing put
 * in which cell.
 */

it('renders the table shell: one header per column with its options, the reorder and actions header cells, and positional cells', function (): void {
    $repeater = Repeater::make('rows')
        ->cloneable()
        ->compact()
        ->table([
            TableColumn::make('Name')->markAsRequired(),
            TableColumn::make('Role')->alignment(Alignment::Center)->width(160)->wrapHeader(),
            TableColumn::make('Active')->hiddenHeaderLabel()->verticalAlignment(VerticalAlignment::End),
        ])
        ->components([
            TextInput::make('name'),
            TextInput::make('role'),
            // A Hidden field consumes no column, so it sits between two consuming
            // components rather than after the last one — past the column budget
            // the counter drops everything, Hidden included (Filament's order).
            Hidden::make('id'),
            Toggle::make('active'),
            TextInput::make('surplus'),
        ]);

    $crawler = renderRepeater($repeater, ['rows' => [['name' => 'a', 'role' => 'r', 'active' => true, 'id' => 7, 'surplus' => 's'], ['name' => 'b']]]);

    $root = $crawler->filter('.sy-fo-table-repeater');
    $headers = $crawler->filter('thead th');

    expect($root->attr('class'))->toContain('sy-compact')
        ->and($headers)->toHaveCount(5)
        ->and($headers->eq(0)->filter('.sy-fo-sr-only')->text())->toBe('Reorder')
        ->and($headers->eq(1)->text())->toContain('Name')
        ->and($headers->eq(1)->filter('.sy-fo-table-repeater-header-required-mark')->text())->toBe('*')
        ->and($headers->eq(2)->attr('class'))->toContain('sy-align-center')->toContain('sy-wrapped')
        ->and($headers->eq(2)->attr('style'))->toContain('width: 160px')
        ->and($headers->eq(3)->filter('.sy-fo-sr-only')->text())->toBe('Active')
        ->and($headers->eq(4)->filter('.sy-fo-sr-only')->text())->toBe('Actions');

    $firstRow = $crawler->filter('tbody tr')->first();
    $cells = $firstRow->filter('td');

    expect($firstRow->attr('data-sortable-item'))->toBe('0')
        ->and($cells)->toHaveCount(6)
        ->and($cells->eq(0)->filter('button[data-sortable-handle]'))->toHaveCount(1)
        ->and($cells->eq(1)->filter('input[data-model$=".name"]'))->toHaveCount(1)
        ->and($cells->eq(3)->attr('class'))->toContain('sy-vertical-align-end')
        ->and($cells->eq(4)->filter('[data-live-name-param="clone"]'))->toHaveCount(1)
        ->and($cells->eq(5)->attr('class'))->toContain('sy-fo-table-repeater-hidden-cell')
        ->and($cells->eq(5)->filter('input[type="hidden"][data-model$=".id"]'))->toHaveCount(1)
        ->and($crawler->filter('input[data-model$=".surplus"]'))->toHaveCount(0)
        ->and($crawler->filter('tbody')->attr('data-controller'))->toBe('sylament--support--sortable')
        ->and($crawler->filter('.sy-fo-repeater-item, .sy-fo-repeater-item-header'))->toHaveCount(0);
});

it("keeps an invisible component's column as a hidden cell and drops the reorder column under one item", function (): void {
    $repeater = Repeater::make('rows')
        ->table([TableColumn::make('Name'), TableColumn::make('Note')])
        ->components([TextInput::make('name'), TextInput::make('note')->hidden()]);

    $crawler = renderRepeater($repeater, ['rows' => [['name' => 'a']]]);

    expect($crawler->filter('thead th'))->toHaveCount(3)
        ->and($crawler->filter('tbody td.sy-hidden'))->toHaveCount(1)
        ->and($crawler->filter('tbody tr td')->eq(1)->attr('class'))->toContain('sy-hidden');
});

it('refuses an action inside a table repeater item', function (): void {
    $repeater = Repeater::make('rows')->table([TableColumn::make('Name')])->components([TextInput::make('name'), Action::make('boom')]);

    // The LogicException is thrown while the parent Schema template renders the
    // repeater, so Twig wraps it.
    expect(fn (): Crawler => renderRepeater($repeater, ['rows' => [['name' => 'a']]]))->toThrow(RuntimeError::class, 'Table repeaters must only contain schema components');
});
