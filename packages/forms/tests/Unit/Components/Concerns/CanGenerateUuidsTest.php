<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Closure;
use LogicException;
use Sylament\Forms\Tests\Fixtures\Components\FixtureItemsField;

/**
 * The RFC 4122 v4 shape: version nibble `4`, variant nibble `8`–`b`.
 */
function uuidV4Pattern(): string
{
    return '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
}

it('generates a distinct rfc 4122 v4 uuid on every call', function (): void {
    $field = FixtureItemsField::make('tags');

    $first = $field->generateUuid();
    $second = $field->generateUuid();

    expect($first)->toMatch(uuidV4Pattern())
        ->and($second)->toMatch(uuidV4Pattern())
        ->and($second)->not->toBe($first);
});

it('generates nothing when generation is turned off', function (): void {
    expect(FixtureItemsField::make('tags')->generateUuidUsing(false)->generateUuid())->toBeNull();
});

it('evaluates a generator closure with the component injected', function (): void {
    $field = FixtureItemsField::make('tags')
        ->generateUuidUsing(fn (FixtureItemsField $component): string => 'key-' . $component->getName());

    expect($field->generateUuid())->toBe('key-tags');
});

it('passes a null generator result through', function (): void {
    expect(FixtureItemsField::make('tags')->generateUuidUsing(fn (): ?string => null)->generateUuid())->toBeNull();
});

it('refuses a generator result that is not a string', function (): void {
    expect(fn (): ?string => FixtureItemsField::make('tags')->generateUuidUsing(fn (): int => 1)->generateUuid())
        ->toThrow(LogicException::class, 'must return a string or null');
});

it('generates a uuid when generation is turned on explicitly', function (): void {
    expect(FixtureItemsField::make('tags')->generateUuidUsing(true)->generateUuid())->toMatch(uuidV4Pattern());
});

it('turns generation off for every instance through fake(), and restores it', function (): void {
    $forget = FixtureItemsField::fake();

    expect($forget)->toBeInstanceOf(Closure::class)
        ->and(FixtureItemsField::make('tags')->generateUuid())->toBeNull();

    $forget();

    expect(FixtureItemsField::make('tags')->generateUuid())->toMatch(uuidV4Pattern());
});
