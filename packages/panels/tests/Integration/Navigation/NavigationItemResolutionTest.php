<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

it('resolves url, active-state and icon closure services from its own injected set', function (): void {
    $stack = $this::getContainer()->get(RequestStack::class);
    $request = Request::create('/admin/settings-page');
    $request->attributes->set('_route', 'sylament.admin.pages.settings-page');

    $stack->push($request);

    try {
        $item = NavigationItem::make('Settings')
            ->url(fn (UrlGeneratorInterface $urlGenerator): string => $urlGenerator->generate('sylament.admin.pages.settings-page'))
            ->isActiveWhen(fn (RequestStack $requestStack): bool => $requestStack->getCurrentRequest()?->attributes->get('_route') === 'sylament.admin.pages.settings-page')
            ->icon(fn (IconManager $icons): string => is_string($resolved = $icons->resolve('missing.alias')) ? $resolved : 'heroicons:home');

        expect($item->getUrl())->toBe('/admin/settings-page')
            ->and($item->isActive())->toBeTrue()
            ->and($item->getIcon())->toBe('heroicons:home');
    } finally {
        $stack->pop();
    }
});
