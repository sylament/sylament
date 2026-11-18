<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Forms\ColoredLevel;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("decorates each item with its icon, an icons map or the state's own icon, and with none otherwise", function (): void {
    $icons = fn (mixed $state, TextColumn $column): array => $this->renderViewComponent($column->record(['level' => $state]))
        ->filter(Selectors::TEXT_ITEM . ' svg')->each(static fn (Crawler $icon): string => $icon->text());
    $bare = $this->renderViewComponent(TextColumn::make('level')->record(['level' => ColoredLevel::Unrated]));

    expect($icons('oak', TextColumn::make('level')->icon('support:star')))->toBe(['Star'])
        ->and($icons('oak', TextColumn::make('level')->icons(['support:heart' => 'oak', 'support:star' => 'ash'])))->toBe(['Heart'])
        ->and($icons(ColoredLevel::High, TextColumn::make('level')))->toBe(['Star'])
        ->and($bare)->toContainElement(Selectors::TEXT_ITEM, 'unrated')
        ->and($bare->filter(Selectors::TEXT_ITEM . ' svg'))->toHaveCount(0);
});

it('stamps exactly one icon-position class per position', function (): void {
    $item = fn (TextColumn $column): Crawler => $this->renderViewComponent($column->record(['name' => 'Lamp'])->icon('support:star'))->filter(Selectors::TEXT_ITEM);
    $before = $item(TextColumn::make('name'));
    $after = $item(TextColumn::make('name')->iconPosition('after'));

    expect($before->filter('svg.sy-ta-text-icon-before:not(.sy-ta-text-icon-after)')->text())->toBe('Star')
        ->and($before->getNode(0)?->firstChild?->nodeName)->toBe('svg')
        ->and($after->filter('svg.sy-ta-text-icon-after:not(.sy-ta-text-icon-before)')->text())->toBe('Star')
        ->and($after->getNode(0)?->lastChild?->nodeName)->toBe('svg');
});

it('resolves an icons map to its last matching entry, and false to no icon without asking the state', function (): void {
    $render = fn (mixed $state, TextColumn $column): Crawler => $this->renderViewComponent($column->record(['level' => $state]));
    $suppressed = $render(ColoredLevel::High, TextColumn::make('level')->icon(false));

    expect($render('oak', TextColumn::make('level')->icons(['support:star', 'support:heart' => 'oak']))->filter(Selectors::TEXT_ITEM . ' svg')->text())->toBe('Heart')
        ->and($render('oak', TextColumn::make('level')->icons(['support:heart' => 'oak', 'support:star']))->filter(Selectors::TEXT_ITEM . ' svg')->text())->toBe('Star')
        ->and($suppressed)->toContainElement(Selectors::TEXT_ITEM, 'high')
        ->and($suppressed->filter(Selectors::TEXT_ITEM . ' svg'))->toHaveCount(0);
});

it("hands an icons closure each badge's own value", function (): void {
    $cell = $this->renderViewComponent(TextColumn::make('status')->record(['status' => ['live', 'draft']])->badge()
        ->icons(['support:star' => fn (string $state): bool => $state === 'live']));

    expect($cell->filter(SupportSelectors::BADGE)->each(static fn (Crawler $badge): array => [$badge->filter(SupportSelectors::BADGE_LABEL)->text(), $badge->filter('svg')->count()]))
        ->toBe([['live', 1], ['draft', 0]]);
});

it('refuses a true icon, naming the column', function (): void {
    expect(fn (): mixed => TextColumn::make('status')->icon(true)->getIcon('live'))
        ->toThrow(LogicException::class, 'Icon of column [status] must be an icon name, an enum, markup, or false; true given.');
});
