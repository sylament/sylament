<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use ArgumentCountError;
use Sylament\Actions\Action;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

/**
 * Wire the component into a real parent Schema so its default child schema can
 * reach the live component through the container chain, as happens in a render.
 */
function wireActions(Actions $actions): Actions
{
    Schema::make(new HasSchemasFixture())->components([$actions])->getComponents();

    return $actions;
}

it('requires the actions argument on make()', function (): void {
    expect(fn (): Actions => Actions::make())->toThrow(ArgumentCountError::class);
});

it('lands the make() actions in the default child schema', function (): void {
    $publish = Action::make('publish');
    $archive = Action::make('archive');

    $actions = wireActions(Actions::make([$publish, $archive]));

    expect($actions->getChildSchema()?->getComponents())->toBe([$publish, $archive]);
});

it('replaces the default child schema components through actions()', function (): void {
    $publish = Action::make('publish');
    $archive = Action::make('archive');

    $actions = wireActions(Actions::make([$publish]))->actions([$archive]);

    expect($actions->getChildSchema()?->getComponents())->toBe([$archive]);
});

it('evaluates an actions closure into the default child schema', function (): void {
    $publish = Action::make('publish');

    $actions = wireActions(Actions::make(fn (): array => [$publish]));

    expect($actions->getChildSchema()?->getComponents())->toBe([$publish]);
});

it('is hidden when every action in the child schema is hidden', function (): void {
    $actions = wireActions(Actions::make([
        Action::make('publish')->hidden(),
        Action::make('archive')->hidden(),
    ]));

    expect($actions->isHidden())->toBeTrue()
        ->and($actions->isVisible())->toBeFalse();
});

it('is hidden with no actions at all', function (): void {
    $actions = wireActions(Actions::make([]));

    expect($actions->isHidden())->toBeTrue();
});

it('stays visible while at least one action is visible', function (): void {
    $actions = wireActions(Actions::make([
        Action::make('publish')->hidden(),
        Action::make('archive'),
    ]));

    expect($actions->isHidden())->toBeFalse()
        ->and($actions->isVisible())->toBeTrue();
});

it('respects its own hidden() flag before inspecting the actions', function (): void {
    $actions = wireActions(Actions::make([Action::make('publish')]))->hidden();

    expect($actions->isHidden())->toBeTrue();
});

it('is not full width by default and flips through fullWidth()', function (): void {
    expect(Actions::make([])->isFullWidth())->toBeFalse()
        ->and(Actions::make([])->fullWidth()->isFullWidth())->toBeTrue()
        ->and(Actions::make([])->fullWidth(fn (): bool => false)->isFullWidth())->toBeFalse();
});
