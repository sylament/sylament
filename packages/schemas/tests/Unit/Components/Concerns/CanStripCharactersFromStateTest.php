<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Tests\Fixtures\Components\MutatingField;

it('strips nothing by default', function (): void {
    expect(MutatingField::make('a')->hasStripCharacters())->toBeFalse()
        ->and(MutatingField::make('a')->getStripCharacters())->toBe([])
        ->and(MutatingField::make('a')->exposeStripCharactersFromState('1, 234'))->toBe('1, 234');
});

it('strips every character of the list', function (): void {
    expect(MutatingField::make('a')->stripCharacters([',', ' '])->exposeStripCharactersFromState('1, 234'))->toBe('1234');
});

it('wraps a single character into a list', function (): void {
    $field = MutatingField::make('a')->stripCharacters(',');

    expect($field->getStripCharacters())->toBe([','])
        ->and($field->hasStripCharacters())->toBeTrue()
        ->and($field->exposeStripCharactersFromState('1,234'))->toBe('1234');
});

it('evaluates a closure argument', function (): void {
    expect(MutatingField::make('a')->stripCharacters(fn (): array => ['-'])->exposeStripCharactersFromState('a-b'))->toBe('ab');
});

it('clears the list with null', function (): void {
    expect(MutatingField::make('a')->stripCharacters(',')->stripCharacters(null)->hasStripCharacters())->toBeFalse();
});

it('leaves a non-string alone', function (): void {
    expect(MutatingField::make('a')->stripCharacters(',')->exposeStripCharactersFromState(['1,2']))->toBe(['1,2']);
});

it('memoises the evaluated list', function (): void {
    $calls = 0;
    $field = MutatingField::make('a')->stripCharacters(function () use (&$calls): string {
        $calls++;

        return ',';
    });

    $field->getStripCharacters();
    $field->getStripCharacters();

    expect($calls)->toBe(1);
});
