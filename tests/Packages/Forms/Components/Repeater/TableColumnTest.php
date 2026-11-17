<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Components\TextInput;
use Sylament\Support\Enums\Alignment;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it("renders each column's header label, for screen readers only when hidden, with a required mark when asked", function (): void {
    $crawler = $this->renderSchemaComponent(
        Repeater::make('lines')
            ->reorderable(false)
            ->deletable(false)
            ->table([TableColumn::make('Name')->markAsRequired(), TableColumn::make('Code')->hiddenHeaderLabel()])
            ->components([TextInput::make('name'), TextInput::make('code')]),
        ['lines' => ['a' => ['name' => 'Ada', 'code' => 'A1']]],
    );
    $headers = $crawler->filter(FormsSelectors::TABLE_REPEATER_HEADER_CELL);

    expect($headers->each(static fn (Crawler $header): string => $header->text()))->toBe(['Name*', 'Code'])
        ->and($headers->eq(0)->filter('sup')->text())->toBe('*')
        ->and($headers->eq(0)->filter('.sy-sr-only'))->toHaveCount(0)
        ->and($headers->eq(1)->filter('.sy-sr-only')->text())->toBe('Code')
        ->and($headers->eq(1)->filter('sup'))->toHaveCount(0);
});

it("puts a column's alignment, width and header wrapping on its header cell", function (): void {
    $crawler = $this->renderSchemaComponent(
        Repeater::make('lines')
            ->reorderable(false)
            ->deletable(false)
            ->table([TableColumn::make('Name')->alignment(Alignment::End)->width('10rem')->wrapHeader(), TableColumn::make('Code')])
            ->components([TextInput::make('name'), TextInput::make('code')]),
        ['lines' => ['a' => ['name' => 'Ada', 'code' => 'A1']]],
    );
    $headers = $crawler->filter(FormsSelectors::TABLE_REPEATER_HEADER_CELL);

    expect($headers->eq(0)->matches(SupportSelectors::align('end') . '.sy-wrapped[style="width: 10rem"]'))->toBeTrue()
        ->and($headers->eq(1)->matches(SupportSelectors::NO_ALIGN . ':not(.sy-wrapped):not([style])'))->toBeTrue();
});
