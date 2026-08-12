<?php

declare(strict_types=1);

use Sylament\Panels\Panel;

it('holds id, path, pages, brand and default flag fluently', function (): void {
    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->pages([stdClass::class])
        ->brandName('Demo')
        ->default();

    expect($panel->getId())->toBe('admin')
        ->and($panel->getPath())->toBe('admin')
        ->and($panel->getPages())->toBe([stdClass::class])
        ->and($panel->getBrandName())->toBe('Demo')
        ->and($panel->isDefault())->toBeTrue();
});

it('throws when the id is read before being set', function (): void {
    Panel::make()->getId();
})->throws(LogicException::class, 'A panel has been registered without an `id()`.');

it('throws when the id is set twice', function (): void {
    Panel::make()->id('admin')->id('other');
})->throws(LogicException::class, 'The panel has already been registered with the ID [admin].');

it('accumulates pages and navigation registrations across calls', function (): void {
    $panel = Panel::make()->pages([stdClass::class])->pages([stdClass::class, SplStack::class]);

    expect($panel->getPages())->toBe([stdClass::class, SplStack::class])
        ->and(Panel::make()->hasCollapsibleNavigationGroups())->toBeTrue()
        ->and(Panel::make()->collapsibleNavigationGroups(false)->hasCollapsibleNavigationGroups())->toBeFalse()
        ->and(Panel::make()->getBrandName())->toBeNull()
        ->and(Panel::make()->isDefault())->toBeFalse();
});

it('evaluates closures for brand, default and collapsible settings', function (): void {
    $panel = Panel::make()
        ->brandName(fn (): string => 'Closure Brand')
        ->default(fn (): bool => true)
        ->collapsibleNavigationGroups(fn (): bool => false);

    expect($panel->getBrandName())->toBe('Closure Brand')
        ->and($panel->isDefault())->toBeTrue()
        ->and($panel->hasCollapsibleNavigationGroups())->toBeFalse();
});
