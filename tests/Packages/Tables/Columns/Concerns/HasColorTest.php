<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Forms\ColoredLevel;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("colours each item by its colour, a colours map or the state's own colour, and leaves it uncoloured otherwise", function (): void {
    $item = fn (mixed $state, TextColumn $column): Crawler => $this->renderViewComponent($column->record(['level' => $state]))->filter(Selectors::TEXT_ITEM);

    expect($item('oak', TextColumn::make('level')->color('success'))->matches(SupportSelectors::color('success')))->toBeTrue()
        ->and($item('oak', TextColumn::make('level')->colors(['danger' => 'ash', 'success' => 'oak']))->matches(SupportSelectors::color('success')))->toBeTrue()
        ->and($item(ColoredLevel::Low, TextColumn::make('level'))->matches(SupportSelectors::color('success')))->toBeTrue()
        ->and($item(ColoredLevel::Unrated, TextColumn::make('level'))->matches(SupportSelectors::GRAY))->toBeTrue()
        ->and($item('oak', TextColumn::make('level'))->matches(SupportSelectors::GRAY))->toBeTrue();
});

it('resolves a colours map to its last matching entry, and false to no colour without asking the state', function (): void {
    $item = fn (mixed $state, TextColumn $column): Crawler => $this->renderViewComponent($column->record(['level' => $state]))->filter(Selectors::TEXT_ITEM);

    expect($item('oak', TextColumn::make('level')->colors(['gray', 'success' => 'oak']))->matches(SupportSelectors::color('success')))->toBeTrue()
        ->and($item('oak', TextColumn::make('level')->colors(['success' => 'oak', 'gray']))->matches(SupportSelectors::color('gray')))->toBeTrue()
        ->and($item(ColoredLevel::Low, TextColumn::make('level')->color(false))->matches(SupportSelectors::GRAY))->toBeTrue();
});

it("hands a colours closure each badge's own value", function (): void {
    $cell = $this->renderViewComponent(TextColumn::make('status')->record(['status' => ['live', 'draft']])->badge()
        ->colors(['success' => fn (string $state): bool => $state === 'live']));

    expect($cell)->toContainElement(SupportSelectors::BADGE . SupportSelectors::color('success'), 'live')
        ->and($cell)->toContainElement(SupportSelectors::BADGE . SupportSelectors::color('primary'), 'draft');
});

it('refuses a true colour, naming the column', function (): void {
    expect(fn (): mixed => TextColumn::make('status')->color(true)->getColor('live'))
        ->toThrow(LogicException::class, 'Color of column [status] must be a palette name, a shade map, or false; true given.');
});
