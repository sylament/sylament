<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;

it('is not collapsible, collapsed, or persisting by default', function (): void {
    $section = Section::make('Profile');

    expect($section->isCollapsible())->toBeFalse()
        ->and($section->isCollapsed())->toBeFalse()
        ->and($section->shouldPersistCollapsed())->toBeFalse();
});

it('becomes collapsible', function (): void {
    expect(Section::make('Profile')->collapsible()->isCollapsible())->toBeTrue();
});

it('disables collapsibility with false', function (): void {
    expect(Section::make('Profile')->collapsible()->collapsible(false)->isCollapsible())->toBeFalse();
});

it('resets collapsibility to the default with null', function (): void {
    expect(Section::make('Profile')->collapsible()->collapsible(null)->isCollapsible())->toBeFalse();
});

it('collapsed() makes the section collapsible', function (): void {
    $section = Section::make('Profile')->collapsed();

    expect($section->isCollapsed())->toBeTrue()
        ->and($section->isCollapsible())->toBeTrue();
});

it('collapsed(false) still makes the section collapsible', function (): void {
    $section = Section::make('Profile')->collapsed(false);

    expect($section->isCollapsed())->toBeFalse()
        ->and($section->isCollapsible())->toBeTrue();
});

it('collapsed() can leave collapsibility untouched', function (): void {
    $section = Section::make('Profile')->collapsed(shouldMakeComponentCollapsible: false);

    expect($section->isCollapsed())->toBeTrue()
        ->and($section->isCollapsible())->toBeFalse();
});

it('collapsed() does not override an explicit collapsible(false)', function (): void {
    expect(Section::make('Profile')->collapsible(false)->collapsed()->isCollapsible())->toBeFalse();
});

it('evaluates a collapsible closure with the section as $component', function (): void {
    $section = Section::make('Profile');

    $received = null;
    $section->collapsible(function (Section $component) use (&$received): bool {
        $received = $component;

        return true;
    });

    expect($section->isCollapsible())->toBeTrue()
        ->and($received)->toBe($section);
});

it('evaluates a collapsed closure with the schema as $item', function (): void {
    $section = Section::make('Profile')->collapsed(fn (?Schema $item): bool => $item instanceof Schema);

    $schema = Schema::make();

    expect($section->isCollapsed())->toBeFalse()
        ->and($section->isCollapsed($schema))->toBeTrue();
});

it('passes the exact schema instance to the $item parameter', function (): void {
    $received = null;
    $section = Section::make('Profile')->collapsed(function (?Schema $item) use (&$received): bool {
        $received = $item;

        return false;
    });

    $schema = Schema::make();
    $section->isCollapsed($schema);

    expect($received)->toBe($schema);
});

it('persists the collapse state on request', function (): void {
    expect(Section::make('Profile')->persistCollapsed()->shouldPersistCollapsed())->toBeTrue();
});

it('evaluates a persistCollapsed closure', function (): void {
    expect(Section::make('Profile')->persistCollapsed(fn (): bool => true)->shouldPersistCollapsed())->toBeTrue();
});
