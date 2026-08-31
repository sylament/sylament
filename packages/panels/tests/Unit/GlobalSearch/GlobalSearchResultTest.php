<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Support\Enums\Size;

it('coerces actions to the link template and small size in the constructor', function (): void {
    $action = Action::make('open');

    $result = new GlobalSearchResult(title: 'Alpha', url: '/a', actions: [$action]);

    expect($result->actions[0]->getTemplate())->toBe(Action::LINK_TEMPLATE)
        ->and($result->actions[0]->getSize())->toBe(Size::Small);
});

it('lets a caller-set template and size win over the defaults', function (): void {
    $action = Action::make('open')->button()->size(Size::Large);

    $result = new GlobalSearchResult(title: 'Alpha', url: '/a', actions: [$action]);

    expect($result->actions[0]->getTemplate())->toBe(Action::BUTTON_TEMPLATE)
        ->and($result->actions[0]->getSize())->toBe(Size::Large);
});

it('filters hidden actions out of getVisibleActions', function (): void {
    $result = new GlobalSearchResult(title: 'Alpha', url: '/a', actions: [
        Action::make('shown'),
        Action::make('hidden')->hidden(),
    ]);

    $visibleActions = $result->getVisibleActions();

    expect($visibleActions)->toHaveCount(1)
        ->and(reset($visibleActions)->getName())->toBe('shown');
});

it('reports detail labels only for an associative details array', function (): void {
    expect(new GlobalSearchResult('A', '/a', details: ['Author' => 'Ada'])->hasDetailLabels())->toBeTrue()
        ->and(new GlobalSearchResult('A', '/a', details: ['Ada'])->hasDetailLabels())->toBeFalse()
        ->and(new GlobalSearchResult('A', '/a')->hasDetailLabels())->toBeFalse();
});
