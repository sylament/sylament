<?php

declare(strict_types=1);

use Sylament\Panels\CurrentPanel;
use Sylament\Panels\PanelRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

it('collects tagged providers into the registry', function (): void {
    $registry = $this::getContainer()->get(PanelRegistry::class);

    expect($registry->get('admin')?->getBrandName())->toBe('Test Admin');
});

it('derives the current panel from the request attributes, statelessly', function (): void {
    $stack = $this::getContainer()->get(RequestStack::class);
    $current = $this::getContainer()->get(CurrentPanel::class);

    expect($current->get())->toBeNull();

    $stack->push(Request::create('/admin'));
    $stack->getCurrentRequest()->attributes->set('_sylament_panel', 'admin');

    expect($current->get()?->getId())->toBe('admin');

    $stack->pop();

    expect($current->get())->toBeNull();
});

it('throws on a panel id the registry does not know', function (): void {
    $stack = $this::getContainer()->get(RequestStack::class);
    $stack->push(Request::create('/nope'));
    $stack->getCurrentRequest()->attributes->set('_sylament_panel', 'nope');

    $this::getContainer()->get(CurrentPanel::class)->get();
})->throws(LogicException::class, 'no such panel is registered');
