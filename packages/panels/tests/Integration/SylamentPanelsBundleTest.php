<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

it('builds navigation items through the container, non-shared', function (): void {
    $this::getContainer();

    expect(NavigationItem::make('A'))->not->toBe(NavigationItem::make('A'));
});

it('injects container services into evaluated closures', function (): void {
    $container = $this::getContainer();
    $container->get(RequestStack::class)->push(Request::create('/admin/articles'));

    $item = NavigationItem::make('Articles')->isActiveWhen(
        fn (RequestStack $requestStack): bool => str_starts_with((string) $requestStack->getCurrentRequest()?->getPathInfo(), '/admin/articles'),
    );

    expect($item->isActive())->toBeTrue();
});
