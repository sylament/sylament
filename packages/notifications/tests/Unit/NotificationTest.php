<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;
use Sylament\Notifications\Tests\Fixtures\ScalableFixtureIcon;
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
        'status' => 'success',
        'color' => 'primary',
        'icon' => 'heroicons:check-circle',
        'iconColor' => 'success',
        'duration' => 4000,
    ])
        ->and(Notification::fromArray($array)->toArray())->toBe($array);
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
