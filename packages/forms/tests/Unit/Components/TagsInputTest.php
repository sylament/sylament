<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use Sylament\Forms\Components\TagsInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\StripCharactersStateCast;

/**
 * Bind the field to a `data`-bag host so `state()` / `getState()` resolve through a real
 * property accessor, which is what `hydrateTags()` reads and writes.
 *
 * @param array<string, mixed> $data
 */
function wireTagsInput(TagsInput $field, array $data = []): TagsInput
{
    $host = new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    return $field;
}

it('defaults to an empty list, the tag placeholder and a 100ms reorder animation', function (): void {
    $field = TagsInput::make('tags');

    expect($field->getDefaultState())->toBe([])
        ->and($field->getPlaceholder())->toBe('New tag')
        ->and($field->getReorderAnimationDuration())->toBe(100)
        ->and($field->isReorderable())->toBeFalse()
        ->and($field->getSeparator())->toBeNull()
        ->and($field->getSplitKeys())->toBe([])
        ->and($field->getSuggestions())->toBe([])
        ->and($field->getTagPrefix())->toBeNull()
        ->and($field->getTagSuffix())->toBeNull()
        ->and($field->getColor())->toBeNull();
});

it('carries the fluent surface, closures included', function (): void {
    $field = TagsInput::make('tags')
        ->tagPrefix(static fn (): string => '#')
        ->tagSuffix('!')
        ->reorderable()
        ->separator()
        ->splitKeys(static fn (): array => [','])
        ->suggestions(static fn (): array => ['alpha', 'beta'])
        ->color('danger');

    expect($field->getTagPrefix())->toBe('#')
        ->and($field->getTagSuffix())->toBe('!')
        ->and($field->isReorderable())->toBeTrue()
        ->and($field->getSeparator())->toBe(',')
        ->and($field->getSplitKeys())->toBe([','])
        ->and($field->getSuggestions())->toBe(['alpha', 'beta'])
        ->and($field->getColor())->toBe('danger')
        ->and(TagsInput::make('tags')->separator('|')->getSeparator())->toBe('|')
        ->and(TagsInput::make('tags')->reorderable(false)->isReorderable())->toBeFalse();
});

it('leaves an array state alone when hydrating', function (): void {
    $field = wireTagsInput(TagsInput::make('tags'), ['tags' => ['alpha', 'beta']]);

    $field->hydrateTags();

    expect($field->getState())->toBe(['alpha', 'beta']);
});

it('empties a non-array state when no separator is set', function (): void {
    $field = wireTagsInput(TagsInput::make('tags'), ['tags' => 'alpha,beta']);

    $field->hydrateTags();

    expect($field->getState())->toBe([]);
});

it('explodes a string state on the separator, collapsing a single blank into an empty list', function (): void {
    $exploded = wireTagsInput(TagsInput::make('tags')->separator(), ['tags' => 'alpha,beta']);
    $blank = wireTagsInput(TagsInput::make('tags')->separator(), ['tags' => '']);
    $unset = wireTagsInput(TagsInput::make('tags')->separator(), []);
    $spaced = wireTagsInput(TagsInput::make('tags')->separator(), ['tags' => ' ']);

    $exploded->hydrateTags();
    $blank->hydrateTags();
    $unset->hydrateTags();
    $spaced->hydrateTags();

    expect($exploded->getState())->toBe(['alpha', 'beta'])
        ->and($blank->getState())->toBe([])
        ->and($unset->getState())->toBe([])
        ->and($spaced->getState())->toBe([]);
});

it('empties a non-string scalar state rather than exploding its cast', function (): void {
    // `strict_types=1` is why: Filament's `explode($separator, $state ?? '')` coerces an int
    // silently, ours would fatal, so anything but a string normalises to null first.
    $field = wireTagsInput(TagsInput::make('tags')->separator(), ['tags' => 123]);

    $field->hydrateTags();

    expect($field->getState())->toBe([]);
});

it('runs the hydration hook through afterStateHydrated', function (): void {
    $field = wireTagsInput(TagsInput::make('tags')->separator(), ['tags' => 'alpha,beta']);

    $field->callAfterStateHydrated();

    expect($field->getState())->toBe(['alpha', 'beta']);
});

it('implodes on dehydration only when a separator is set', function (): void {
    $joined = wireTagsInput(TagsInput::make('tags')->separator());
    $listed = wireTagsInput(TagsInput::make('tags'));

    expect($joined->getStateToDehydrate(['alpha', 'beta']))->toBe(['data.tags' => 'alpha,beta'])
        ->and($listed->getStateToDehydrate(['alpha', 'beta']))->toBe(['data.tags' => ['alpha', 'beta']]);
});

it('dehydrates an emptied list to an empty string when a separator is set', function (): void {
    // The `'' → null` normalisation runs before the closure, so it never sees the imploded
    // value — Filament's own behaviour, and the reason an emptied separator field saves `''`.
    $field = wireTagsInput(TagsInput::make('tags')->separator());

    expect($field->getStateToDehydrate([]))->toBe(['data.tags' => '']);
});

it('trims every element of the dehydrated state', function (): void {
    $field = TagsInput::make('tags')->trim();

    expect($field->mutateDehydratedState(['  alpha ', "beta\n"]))->toBe(['alpha', 'beta'])
        ->and($field->mutateDehydratedState('  alpha '))->toBe('alpha')
        ->and($field->mutatesDehydratedState())->toBeTrue()
        ->and(TagsInput::make('tags')->mutatesDehydratedState())->toBeFalse()
        ->and(TagsInput::make('tags')->mutateDehydratedState(['  alpha ']))->toBe(['  alpha ']);
});

it('strips then trims every element of the validated state', function (): void {
    $field = TagsInput::make('tags')->stripCharacters('-')->trim();

    expect($field->mutateStateForValidation([' -al-pha- ', '-beta']))->toBe(['alpha', 'beta'])
        ->and($field->mutateStateForValidation(' -alpha- '))->toBe('alpha')
        ->and($field->mutatesStateForValidation())->toBeTrue()
        ->and(TagsInput::make('tags')->stripCharacters('-')->mutatesStateForValidation())->toBeTrue()
        ->and(TagsInput::make('tags')->trim()->mutatesStateForValidation())->toBeTrue()
        ->and(TagsInput::make('tags')->mutatesStateForValidation())->toBeFalse();
});

it('appends a strip-characters state cast only when characters are set', function (): void {
    $stripping = TagsInput::make('tags')->stripCharacters(['-', '_']);

    expect(array_last($stripping->getDefaultStateCasts()))->toBeInstanceOf(StripCharactersStateCast::class)
        ->and(TagsInput::make('tags')->getDefaultStateCasts())->toBe([]);
});

it('strips characters off every element through the state cast', function (): void {
    $cast = array_last(TagsInput::make('tags')->stripCharacters('-')->getDefaultStateCasts());

    expect($cast->get(['al-pha', 'be-ta']))->toBe(['alpha', 'beta']);
});
