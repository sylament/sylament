<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;

it('returns every option when none is disabled', function (): void {
    $field = FixtureOptionsField::make('status')->options(['draft' => 'Draft', 'published' => 'Published']);

    expect($field->getEnabledOptions())->toBe(['draft' => 'Draft', 'published' => 'Published'])
        ->and($field->hasDisabledOptions())->toBeFalse();
});

it('flattens option groups into one map', function (): void {
    $field = FixtureOptionsField::make('status')->options([
        'Live' => ['published' => 'Published'],
        'Not live' => ['draft' => 'Draft', 'archived' => 'Archived'],
    ]);

    expect($field->getEnabledOptions())->toBe([
        'published' => 'Published',
        'draft' => 'Draft',
        'archived' => 'Archived',
    ]);
});

it('filters options disabled by value', function (): void {
    $field = FixtureOptionsField::make('status')
        ->options(['draft' => 'Draft', 'published' => 'Published'])
        ->disableOptionWhen(fn (string|int $value): bool => $value === 'published');

    expect($field->getEnabledOptions())->toBe(['draft' => 'Draft'])
        ->and($field->hasDisabledOptions())->toBeTrue()
        ->and($field->isOptionDisabled('published', 'Published'))->toBeTrue()
        ->and($field->isOptionDisabled('draft', 'Draft'))->toBeFalse();
});

it('injects the label into the callback', function (): void {
    $field = FixtureOptionsField::make('status')
        ->options(['draft' => 'Draft'])
        ->disableOptionWhen(fn (string $label): bool => $label === 'Draft');

    expect($field->isOptionDisabled('draft', 'Draft'))->toBeTrue();
});

it('merges a second callback when asked', function (): void {
    $field = FixtureOptionsField::make('status')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->disableOptionWhen(fn (string|int $value): bool => $value === 'a')
        ->disableOptionWhen(fn (string|int $value): bool => $value === 'b', merge: true);

    expect($field->getEnabledOptions())->toBe(['c' => 'C']);
});

it('replaces the callback when not merging', function (): void {
    $field = FixtureOptionsField::make('status')
        ->options(['a' => 'A', 'b' => 'B'])
        ->disableOptionWhen(fn (string|int $value): bool => $value === 'a')
        ->disableOptionWhen(fn (string|int $value): bool => $value === 'b');

    expect($field->getEnabledOptions())->toBe(['a' => 'A']);
});

it('clears every callback when disabling is switched off', function (): void {
    $field = FixtureOptionsField::make('status')
        ->options(['a' => 'A', 'b' => 'B'])
        ->disableOptionWhen(fn (string|int $value): bool => $value === 'a')
        ->disableOptionWhen(null);

    expect($field->hasDisabledOptions())->toBeFalse()
        ->and($field->getEnabledOptions())->toBe(['a' => 'A', 'b' => 'B']);
});

it('ignores a null callback on merge', function (): void {
    $field = FixtureOptionsField::make('status')
        ->options(['a' => 'A', 'b' => 'B'])
        ->disableOptionWhen(null, merge: true);

    expect($field->hasDisabledOptions())->toBeFalse()
        ->and($field->getEnabledOptions())->toBe(['a' => 'A', 'b' => 'B']);
});

it('reports a dynamic disabling callback', function (): void {
    expect(FixtureOptionsField::make('status')->hasDynamicDisabledOptions())->toBeFalse()
        ->and(FixtureOptionsField::make('status')->disableOptionWhen(true)->hasDynamicDisabledOptions())->toBeFalse()
        ->and(FixtureOptionsField::make('status')->disableOptionWhen(fn (): bool => true)->hasDynamicDisabledOptions())->toBeTrue();
});

it('reports a placeholder as selectable by default', function (): void {
    expect(FixtureOptionsField::make('status')->canSelectPlaceholder())->toBeTrue()
        ->and(FixtureOptionsField::make('status')->selectablePlaceholder(false)->canSelectPlaceholder())->toBeFalse();
});

it('evaluates closure options and reports them as dynamic', function (): void {
    $static = FixtureOptionsField::make('status')->options(['a' => 'A']);
    $dynamic = FixtureOptionsField::make('status')->options(fn (): array => ['a' => 'A']);

    expect($static->hasDynamicOptions())->toBeFalse()
        ->and($dynamic->hasDynamicOptions())->toBeTrue()
        ->and($dynamic->getOptions())->toBe(['a' => 'A']);
});

it('returns an empty option list when unset', function (): void {
    expect(FixtureOptionsField::make('status')->getOptions())->toBe([]);
});
