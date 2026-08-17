<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionBadge;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionSeverity;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;
use Twig\Markup;

it('gives no option an icon by default', function (): void {
    $field = FixtureOptionsField::make('status');

    expect($field->getIcons())->toBe([])
        ->and($field->getIcon('draft'))->toBeNull();
});

it('keeps a literal icon map', function (): void {
    $markup = new Markup('<svg></svg>', 'UTF-8');

    $field = FixtureOptionsField::make('status')->icons([
        'draft' => 'heroicon-o-pencil',
        'published' => $markup,
    ]);

    expect($field->getIcon('draft'))->toBe('heroicon-o-pencil')
        ->and($field->getIcon('published'))->toBe($markup)
        ->and($field->getIcon('archived'))->toBeNull();
});

it('evaluates an icons closure', function (): void {
    expect(FixtureOptionsField::make('status')->icons(fn (): array => ['draft' => 'heroicon-o-pencil'])->getIcons())
        ->toBe(['draft' => 'heroicon-o-pencil']);
});

it('derives icons from an enum implementing HasIcon, keeping the iconless case', function (): void {
    $field = FixtureOptionsField::make('severity')->enum(OptionSeverity::class);

    expect($field->getIcons())->toBe([
        'critical' => 'heroicon-o-exclamation-triangle',
        'routine' => null,
    ])
        ->and(array_key_exists('routine', $field->getIcons()))->toBeTrue()
        ->and($field->getIcon('critical'))->toBe('heroicon-o-exclamation-triangle')
        ->and($field->getIcon('routine'))->toBeNull();
});

it('keys an iconed pure UnitEnum by case name', function (): void {
    expect(FixtureOptionsField::make('badge')->enum(OptionBadge::class)->getIcons())
        ->toBe(['Fresh' => 'heroicon-o-sparkles', 'Archived' => 'heroicon-o-archive-box']);
});

it('derives nothing from an enum without the icon contract', function (): void {
    expect(FixtureOptionsField::make('status')->enum(OptionStatus::class)->getIcons())->toBe([]);
});

it('lets a literal icon map win over the enum', function (): void {
    expect(FixtureOptionsField::make('severity')->enum(OptionSeverity::class)->icons(['routine' => 'heroicon-o-clock'])->getIcons())
        ->toBe(['routine' => 'heroicon-o-clock']);
});

it('memoises the icon map', function (): void {
    $calls = 0;

    $field = FixtureOptionsField::make('status')->icons(function () use (&$calls): array {
        $calls++;

        return ['draft' => 'heroicon-o-pencil'];
    });

    $field->getIcons();
    $field->getIcons();

    expect($calls)->toBe(1);
});

it('clears the memoised map when the icons are set again', function (): void {
    $field = FixtureOptionsField::make('status')->icons(['draft' => 'heroicon-o-pencil']);

    expect($field->getIcons())->toBe(['draft' => 'heroicon-o-pencil']);

    $field->icons(['published' => 'heroicon-o-check']);

    expect($field->getIcons())->toBe(['published' => 'heroicon-o-check']);
});
