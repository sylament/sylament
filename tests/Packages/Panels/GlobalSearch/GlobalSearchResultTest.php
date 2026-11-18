<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders its actions as small links, and shows only the visible ones', function (): void {
    $result = new GlobalSearchResult(title: 'Moon', url: '/moon', actions: [
        Action::make('open')->url('/open'),
        Action::make('archive')->url('/archive')->hidden(),
    ]);

    expect(array_map(fn (Action $action): array => [$action->getName(), $this->renderViewComponent($action)->filter(SupportSelectors::SMALL_LINK)->count()], array_values($result->getVisibleActions())))->toBe([['open', 1]])
        ->and($this->renderViewComponent(Action::make('open')->url('/open'))->filter(SupportSelectors::SMALL_LINK))->toHaveCount(0);
});

it('labels its details when they are a map, and never when they are a list', function (): void {
    expect(new GlobalSearchResult(title: 'Moon', url: '/moon', details: ['Author' => 'Ada'])->hasDetailLabels())->toBeTrue()
        ->and(new GlobalSearchResult(title: 'Moon', url: '/moon', details: ['Ada'])->hasDetailLabels())->toBeFalse();
});
