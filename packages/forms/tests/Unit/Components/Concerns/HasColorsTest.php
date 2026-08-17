<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionBadge;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionSeverity;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;

it('colours no option by default', function (): void {
    $field = FixtureOptionsField::make('status');

    expect($field->getColors())->toBe([])
        ->and($field->getColor('draft'))->toBeNull();
});

it('keeps a literal colour map', function (): void {
    $field = FixtureOptionsField::make('status')->colors([
        'draft' => 'gray',
        'published' => [500 => '#22c55e'],
    ]);

    expect($field->getColor('draft'))->toBe('gray')
        ->and($field->getColor('published'))->toBe([500 => '#22c55e'])
        ->and($field->getColor('archived'))->toBeNull();
});

it('evaluates a colors closure', function (): void {
    expect(FixtureOptionsField::make('status')->colors(fn (): array => ['draft' => 'gray'])->getColors())
        ->toBe(['draft' => 'gray']);
});

it('derives colours from an enum implementing HasColor, keeping the colourless case', function (): void {
    $field = FixtureOptionsField::make('severity')->enum(OptionSeverity::class);

    expect($field->getColors())->toBe(['critical' => 'danger', 'routine' => null])
        ->and(array_key_exists('routine', $field->getColors()))->toBeTrue()
        ->and($field->getColor('critical'))->toBe('danger')
        ->and($field->getColor('routine'))->toBeNull();
});

it('keys a coloured pure UnitEnum by case name', function (): void {
    expect(FixtureOptionsField::make('badge')->enum(OptionBadge::class)->getColors())
        ->toBe(['Fresh' => 'info', 'Archived' => 'gray']);
});

it('derives nothing from an enum without the colour contract', function (): void {
    expect(FixtureOptionsField::make('status')->enum(OptionStatus::class)->getColors())->toBe([]);
});

it('lets a literal colour map win over the enum', function (): void {
    expect(FixtureOptionsField::make('severity')->enum(OptionSeverity::class)->colors(['routine' => 'success'])->getColors())
        ->toBe(['routine' => 'success']);
});

it('memoises the colour map', function (): void {
    $calls = 0;

    $field = FixtureOptionsField::make('status')->colors(function () use (&$calls): array {
        $calls++;

        return ['draft' => 'gray'];
    });

    $field->getColors();
    $field->getColors();

    expect($calls)->toBe(1);
});

it('clears the memoised map when the colours are set again', function (): void {
    $field = FixtureOptionsField::make('status')->colors(['draft' => 'gray']);

    expect($field->getColors())->toBe(['draft' => 'gray']);

    $field->colors(['published' => 'success']);

    expect($field->getColors())->toBe(['published' => 'success']);
});

it('falls back to the enum again when the colours are cleared', function (): void {
    $field = FixtureOptionsField::make('severity')->enum(OptionSeverity::class)->colors(['routine' => 'success']);

    expect($field->getColors())->toBe(['routine' => 'success']);

    $field->colors(null);

    expect($field->getColors())->toBe(['critical' => 'danger', 'routine' => null]);
});
