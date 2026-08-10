<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Notifications\Notification;
use Sylament\Notifications\Tests\Fixtures\ScalableFixtureIcon;
use Sylament\Support\Enums\Size;
use Symfony\Component\Uid\Uuid;

it('mints a uuid id when make() is called without one', function (): void {
    expect(Uuid::isValid(Notification::make()->getId()))->toBeTrue()
        ->and(Notification::make()->getId())->not->toBe(Notification::make()->getId());
});

it('round-trips through toArray() and fromArray()', function (): void {
    $notification = Notification::make('fixed-id')
        ->title('Saved')
        ->body('The article was saved.')
        ->success()
        ->color('primary')
        ->icon('heroicons:check-circle')
        ->iconColor('success')
        ->seconds(4);

    $array = $notification->toArray();

    expect($array)->toBe([
        'id' => 'fixed-id',
        'title' => 'Saved',
        'body' => 'The article was saved.',
        'actions' => [],
        'status' => 'success',
        'color' => 'primary',
        'icon' => 'heroicons:check-circle',
        'iconColor' => 'success',
        'duration' => 4000,
    ])
        ->and(Notification::fromArray($array)->toArray())->toBe($array);
});

it('round-trips actions and groups, re-viewed as small links', function (): void {
    $array = Notification::make('n')
        ->actions([
            Action::make('view')->url('/x'),
            ActionGroup::make([Action::make('undo')->dispatch('undo')]),
        ])
        ->toArray();

    expect($array['actions'])->toHaveCount(2)
        ->and($array['actions'][0]['name'])->toBe('view')
        ->and($array['actions'][0]['template'])->toBe(Action::LINK_TEMPLATE)
        ->and($array['actions'][0]['size'])->toBe(Size::Small->value)
        ->and($array['actions'][1]['triggerTemplate'])->toBe(ActionGroup::LINK_TEMPLATE)
        ->and($array['actions'][1]['size'])->toBe(Size::Small->value);

    $restored = Notification::fromArray($array);

    expect($restored->getActions()[0])->toBeInstanceOf(Action::class)
        ->and($restored->getActions()[1])->toBeInstanceOf(ActionGroup::class)
        ->and($restored->toArray())->toBe($array);
});

it('serializes a keyed actions array as a list, so the round trip is idempotent', function (): void {
    $array = Notification::make('n')->actions(['undo' => Action::make('undo')])->toArray();

    expect(array_is_list($array['actions']))->toBeTrue()
        ->and(Notification::fromArray($array)->toArray())->toBe($array);
});

it('drops a non-array actions payload and the junk entries inside one', function (): void {
    expect(Notification::fromArray(['id' => 'x', 'actions' => 'nope'])->getActions())->toBe([])
        ->and(Notification::fromArray(['id' => 'x', 'actions' => ['nope', ['name' => 'view']]])->getActions())
        ->toHaveCount(1);
});

it('applies fromArray() defaults for absent keys and ignores junk types', function (): void {
    $restored = Notification::fromArray(['id' => 'x', 'title' => 5, 'duration' => null]);

    expect($restored->getId())->toBe('x')
        ->and($restored->getTitle())->toBeNull()
        ->and($restored->getDuration())->toBe(6000)
        ->and($restored->getStatus())->toBeNull();
});

it('restores an array shade map and drops an array holding a non-string shade', function (): void {
    $shades = [50 => '#eef2ff', 500 => '#3333ff'];

    $restored = Notification::fromArray(['id' => 'x', 'color' => $shades, 'iconColor' => $shades]);

    expect($restored->getColor())->toBe($shades)
        ->and($restored->getIconColor())->toBe($shades)
        ->and($restored->toArray()['color'])->toBe($shades);

    $mixed = Notification::fromArray(['id' => 'x', 'color' => [50 => '#eef2ff', 500 => 3], 'iconColor' => [500 => 3]]);

    expect($mixed->getColor())->toBeNull()
        ->and($mixed->getIconColor())->toBeNull();
});

it('evaluates closure title and body at read time so toArray() stores values', function (): void {
    $array = Notification::make('n')->title(fn (): string => 'Lazy')->toArray();

    expect($array['title'])->toBe('Lazy');
});

it('carries a ScalableIcon through the round trip unflattened', function (): void {
    $array = Notification::make('n')->icon(ScalableFixtureIcon::Check)->toArray();

    expect($array['icon'])->toBe(ScalableFixtureIcon::Check)
        ->and(Notification::fromArray($array)->getIcon())->toBe(ScalableFixtureIcon::Check);
});
