<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Panel;
use Twig\Markup;

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
        ->and(Panel::make()->getResources())->toBe([])
        ->and(Panel::make()->hasCollapsibleNavigationGroups())->toBeTrue()
        ->and(Panel::make()->collapsibleNavigationGroups(false)->hasCollapsibleNavigationGroups())->toBeFalse()
        ->and(Panel::make()->getBrandName())->toBeNull()
        ->and(Panel::make()->isDefault())->toBeFalse();
});

it('accumulates resources across calls, de-duplicated and re-indexed', function (): void {
    $panel = Panel::make()
        ->resources([stdClass::class])
        ->resources([stdClass::class, SplStack::class]);

    expect($panel->getResources())->toBe([stdClass::class, SplStack::class]);
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

it('holds the brand logo settings fluently, defaulting to null', function (): void {
    $logo = new Markup('<svg></svg>', 'UTF-8');

    $panel = Panel::make()
        ->brandLogo('/images/logo.svg')
        ->darkModeBrandLogo($logo)
        ->brandLogoHeight('2rem');

    expect($panel->getBrandLogo())->toBe('/images/logo.svg')
        ->and($panel->getDarkModeBrandLogo())->toBe($logo)
        ->and($panel->getBrandLogoHeight())->toBe('2rem')
        ->and(Panel::make()->getBrandLogo())->toBeNull()
        ->and(Panel::make()->getDarkModeBrandLogo())->toBeNull()
        ->and(Panel::make()->getBrandLogoHeight())->toBeNull();
});

it('evaluates closures for the brand logo settings', function (): void {
    $panel = Panel::make()
        ->brandLogo(fn (): string => '/images/closure-logo.svg')
        ->darkModeBrandLogo(fn (): string => '/images/closure-logo-dark.svg')
        ->brandLogoHeight(fn (): string => '1.75rem');

    expect($panel->getBrandLogo())->toBe('/images/closure-logo.svg')
        ->and($panel->getDarkModeBrandLogo())->toBe('/images/closure-logo-dark.svg')
        ->and($panel->getBrandLogoHeight())->toBe('1.75rem');
});

it('holds the sub-navigation position fluently, defaulting to the start edge', function (): void {
    expect(Panel::make()->getSubNavigationPosition())->toBe(SubNavigationPosition::Start)
        ->and(Panel::make()->subNavigationPosition(SubNavigationPosition::Top)->getSubNavigationPosition())->toBe(SubNavigationPosition::Top)
        ->and(Panel::make()->subNavigationPosition(SubNavigationPosition::End)->subNavigationPosition(null)->getSubNavigationPosition())->toBe(SubNavigationPosition::Start);
});

it('evaluates a closure for the sub-navigation position', function (): void {
    $panel = Panel::make()->subNavigationPosition(fn (): SubNavigationPosition => SubNavigationPosition::End);

    expect($panel->getSubNavigationPosition())->toBe(SubNavigationPosition::End);
});
