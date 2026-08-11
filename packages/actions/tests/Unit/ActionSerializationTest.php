<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Tests\Fixtures\ScalableFixtureIcon;

it('round-trips a styled url action through toArray and fromArray', function (): void {
    $array = Action::make('view')
        ->label('View post')
        ->color('gray')
        ->icon('heroicons:eye')
        ->outlined()
        ->url('/posts/5', shouldOpenInNewTab: true)
        ->toArray();

    $restored = Action::fromArray($array);

    expect($restored->getName())->toBe('view')
        ->and($restored->getLabel())->toBe('View post')
        ->and($restored->getUrl())->toBe('/posts/5')
        ->and($restored->shouldOpenUrlInNewTab())->toBeTrue()
        ->and($restored->toArray())->toBe($array);
});

it('round-trips the dispatch trio and close', function (): void {
    $array = Action::make('undo')->dispatchTo('App:PostList', 'undoEditingPost', ['post' => 5])->close()->toArray();
    $restored = Action::fromArray($array);

    expect($restored->getEvent())->toBe('undoEditingPost')
        ->and($restored->getDispatchDirection())->toBe('to')
        ->and($restored->getDispatchToComponent())->toBe('App:PostList')
        ->and($restored->getEventData())->toBe(['post' => 5])
        ->and($restored->shouldClose())->toBeTrue();
});

it('degrades a to-direction with a junk component to a plain dispatch', function (): void {
    $tampered = Action::make('a')->dispatch('x')->toArray();
    $tampered['dispatchDirection'] = 'to';
    $tampered['dispatchToComponent'] = null;

    expect(Action::fromArray($tampered)->getDispatchDirection())->toBeFalse();
});

it('round-trips the template variant and drops an unknown template', function (): void {
    expect(Action::fromArray(Action::make('a')->iconButton()->toArray())->getTemplate())
        ->toBe(Action::ICON_BUTTON_TEMPLATE);

    $tampered = Action::make('a')->toArray();
    $tampered['template'] = '@Evil/anything.html.twig';

    expect(Action::fromArray($tampered)->getTemplate())->toBe(Action::BUTTON_TEMPLATE);
});

it('serializes no closure-bearing or modal keys and none of the recorded omissions', function (): void {
    $array = Action::make('a')->requiresConfirmation()->action(fn (): null => null)->toArray();

    expect($array)->not->toHaveKey('action')
        ->not->toHaveKey('modalHeading')
        ->not->toHaveKey('alpineClickHandler')
        ->not->toHaveKey('shouldPostToUrl');
});

it('carries a ScalableIcon through the round trip unflattened', function (): void {
    $array = Action::make('a')->icon(ScalableFixtureIcon::Check)->toArray();

    expect($array['icon'])->toBe(ScalableFixtureIcon::Check)
        ->and(Action::fromArray($array)->getIcon())->toBe(ScalableFixtureIcon::Check);
});

it('round-trips a group with its child actions and dropdown config', function (): void {
    $array = ActionGroup::make()->actions([Action::make('one'), Action::make('two')->close()])->toArray();
    $restored = ActionGroup::fromArray($array);

    expect($restored->getFlatActions())->toHaveKeys(['one', 'two'])
        ->and($restored->toArray())->toBe($array);
});

it('round-trips a group nested in a group beside a flat sibling', function (): void {
    $array = ActionGroup::make()
        ->actions([
            ActionGroup::make()->actions([Action::make('edit'), Action::make('delete')]),
            Action::make('view'),
        ])
        ->toArray();

    $restored = ActionGroup::fromArray($array);
    $members = $restored->getActions();

    expect($restored->getFlatActions())->toHaveKeys(['edit', 'delete', 'view'])
        ->and($members[0])->toBeInstanceOf(ActionGroup::class)
        ->and($members[0]->getFlatActions())->toHaveKeys(['edit', 'delete'])
        ->and($members[1])->toBeInstanceOf(Action::class)
        ->and($restored->toArray())->toBe($array);
});

it('writes the seventeen group keys in order', function (): void {
    expect(array_keys(ActionGroup::make()->actions([Action::make('one')])->toArray()))->toBe([
        'actions',
        'color',
        'dropdownMaxHeight',
        'dropdownOffset',
        'dropdownPlacement',
        'dropdownWidth',
        'extraAttributes',
        'hasDropdown',
        'hasDropdownFlip',
        'hasDropdownTeleport',
        'icon',
        'iconSize',
        'isOutlined',
        'label',
        'size',
        'tooltip',
        'triggerTemplate',
    ]);
});

/**
 * The serialized value is the *resolved* one, so a group teleporting only because
 * a host stamped `defaultDropdownTeleport()` restores explicitly teleported.
 */
it('round-trips a teleporting group as an explicit decision', function (): void {
    $array = ActionGroup::make()->actions([Action::make('one')])->dropdownTeleport()->toArray();
    $restored = ActionGroup::fromArray($array);

    expect($array['hasDropdownTeleport'])->toBeTrue()
        ->and($restored->hasDropdownTeleport())->toBeTrue()
        ->and($restored->toArray())->toBe($array);

    $defaulted = ActionGroup::make()->actions([Action::make('one')])->defaultDropdownTeleport()->toArray();

    expect($defaulted['hasDropdownTeleport'])->toBeTrue()
        ->and(ActionGroup::fromArray($defaulted)->hasDropdownTeleport())->toBeTrue();
});

it('restores a group with no teleport key as unteleported', function (): void {
    $array = ActionGroup::make()->actions([Action::make('one')])->toArray();
    unset($array['hasDropdownTeleport']);

    expect(ActionGroup::fromArray($array)->hasDropdownTeleport())->toBeFalse();
});

it('round-trips a link-triggered group', function (): void {
    $array = ActionGroup::make()->actions([Action::make('one')])->link()->toArray();
    $restored = ActionGroup::fromArray($array);

    expect($restored->getTriggerTemplate())->toBe(ActionGroup::LINK_TEMPLATE)
        ->and($restored->isLink())->toBeTrue()
        ->and($restored->toArray())->toBe($array);
});
