<?php

declare(strict_types=1);

use Sylament\Panels\PanelRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

it('resolves page item urls and active state through container services', function (): void {
    $panel = $this::getContainer()->get(PanelRegistry::class)->get('admin');

    $stack = $this::getContainer()->get(RequestStack::class);
    $request = Request::create('/admin/settings-page');
    $request->attributes->set('_route', 'sylament.admin.pages.settings-page');

    $stack->push($request);

    $navigation = $panel->getNavigation();
    $content = null;

    foreach ($navigation as $group) {
        if ($group->getLabel() === 'Content') {
            $content = $group;
        }
    }

    $settings = $content?->getItems()[0] ?? null;

    expect($settings?->getUrl())->toBe('/admin/settings-page')
        ->and($settings->isActive())->toBeTrue()
        ->and($content->isActive())->toBeTrue();

    $stack->pop();
});
