<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Tests\Fixtures\Records\Status;
use Twig\Markup;

it('json encodes an array state', function (): void {
    expect(TextColumn::make('meta')->formatState(['a' => 1]))->toBe('{"a":1}');
});

it('unwraps a state implementing the label contract', function (): void {
    expect(TextColumn::make('status')->formatState(Status::Published))->toBe('Published');
});

it('limits characters and appends the end only when longer', function (): void {
    expect(TextColumn::make('title')->limit(5)->formatState('abcdefghij'))->toBe('abcde...')
        ->and(TextColumn::make('title')->limit(5)->formatState('abc'))->toBe('abc')
        ->and(TextColumn::make('title')->limit(5, '…')->formatState('abcdefghij'))->toBe('abcde…');
});

it('trims trailing whitespace before appending the limit end', function (): void {
    expect(TextColumn::make('title')->limit(3)->formatState('ab cdef'))->toBe('ab...');
});

it('limits words and appends the end only when longer', function (): void {
    expect(TextColumn::make('title')->words(2)->formatState('one two three four'))->toBe('one two...')
        ->and(TextColumn::make('title')->words(5)->formatState('one two'))->toBe('one two');
});

it('concatenates a prefix and a suffix', function (): void {
    expect(TextColumn::make('title')->prefix('# ')->suffix(' !')->formatState('Airbag'))->toBe('# Airbag !');
});

it('escapes plain state when an affix is markup and returns markup', function (): void {
    $result = TextColumn::make('title')->prefix(new Markup('<b>#</b>', 'UTF-8'))->formatState('a<b');

    expect($result)->toBeInstanceOf(Markup::class)
        ->and((string) $result)->toBe('<b>#</b>a&lt;b');
});

it('escapes a plain affix when the state is markup', function (): void {
    $result = TextColumn::make('title')
        ->formatStateUsing(fn (): Markup => new Markup('<b>hi</b>', 'UTF-8'))
        ->suffix(' <x>')
        ->formatState('ignored');

    expect($result)->toBeInstanceOf(Markup::class)
        ->and((string) $result)->toBe('<b>hi</b> &lt;x&gt;');
});

it('skips truncation for a markup state', function (): void {
    $result = TextColumn::make('title')
        ->formatStateUsing(fn (): Markup => new Markup('<b>abcdefghij</b>', 'UTF-8'))
        ->limit(3)
        ->formatState('ignored');

    expect($result)->toBeInstanceOf(Markup::class)
        ->and((string) $result)->toBe('<b>abcdefghij</b>');
});

it('applies the format callback before the transforms', function (): void {
    expect(TextColumn::make('title')->formatStateUsing(fn (string $state): string => $state . $state)->limit(3)->formatState('airbag'))
        ->toBe('air...');
});
