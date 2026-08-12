<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Page;

it('exposes title, heading fallback, and navigation statics', function (): void {
    $page = new class extends Page {
        protected static ?string $title = 'Dashboard';

        protected static ?string $navigationLabel = 'Home';

        protected static ?int $navigationSort = 2;
    };

    expect($page->getTitle())->toBe('Dashboard')
        ->and($page->getHeading())->toBe('Dashboard')
        ->and($page->getSubheading())->toBeNull()
        ->and($page::getNavigationLabel())->toBe('Home')
        ->and($page::getNavigationSort())->toBe(2)
        ->and($page::getNavigationIcon())->toBeNull()
        ->and($page::getNavigationGroup())->toBeNull();
});

it('defaults everything null', function (): void {
    $page = new class extends Page {};

    expect($page->getTitle())->toBeNull()
        ->and($page->getHeading())->toBeNull()
        ->and($page::getNavigationLabel())->toBeNull();
});
