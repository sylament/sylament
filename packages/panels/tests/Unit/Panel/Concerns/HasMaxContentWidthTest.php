<?php

declare(strict_types=1);

use Sylament\Panels\Panel;
use Sylament\Support\Enums\Width;

it('names no content width by default', function (): void {
    $panel = Panel::make();

    expect($panel->getMaxContentWidth())->toBeNull()
        ->and($panel->getSimplePageMaxContentWidth())->toBeNull();
});

it('takes a width as an enum, as a raw class, and from a closure', function (): void {
    expect(Panel::make()->maxContentWidth(Width::FiveExtraLarge)->getMaxContentWidth())->toBe(Width::FiveExtraLarge)
        ->and(Panel::make()->maxContentWidth('max-w-[42rem]')->getMaxContentWidth())->toBe('max-w-[42rem]')
        ->and(Panel::make()->maxContentWidth(fn (): Width => Width::Prose)->getMaxContentWidth())->toBe(Width::Prose);
});

it('takes a simple-page width of its own', function (): void {
    $panel = Panel::make()
        ->maxContentWidth(Width::SevenExtraLarge)
        ->simplePageMaxContentWidth(fn (): Width => Width::Medium);

    expect($panel->getSimplePageMaxContentWidth())->toBe(Width::Medium)
        ->and($panel->getMaxContentWidth())->toBe(Width::SevenExtraLarge);
});
