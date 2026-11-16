<?php

declare(strict_types=1);

use Sylament\Support\Enums\IconSize;
use Sylament\Support\Twig\ComponentAttributeBag;

it('normalizes class into a token list and style into a declaration list', function (array $attributes, array $normalized): void {
    expect(new ComponentAttributeBag($attributes)->getAttributes())->toBe($normalized);
})->with([
    'a class string' => [['class' => '  a   b '], ['class' => ['a', 'b']]],
    'a conditional class map' => [['class' => ['a' => true, 'b' => false, 'c']], ['class' => ['a', 'c']]],
    'a nested class list' => [['class' => ['a', ['b', ['c' => true]]]], ['class' => ['a', 'b', 'c']]],
    'a style string, a semicolon inside url() or quotes kept' => [
        ['style' => "color: red; background: url(a;b); content: 'x;y';"],
        ['style' => ['color: red', 'background: url(a;b)', "content: 'x;y'"]],
    ],
    'a style map' => [['style' => ['color' => 'red', 'margin' => 0]], ['style' => ['color: red', 'margin: 0']]],
]);

it('merges defaults first, its own scalar winning and its class and style appended, duplicates kept and itself unchanged', function (): void {
    $bag = new ComponentAttributeBag(['class' => 'a', 'style' => 'color: red', 'id' => 'mine']);

    expect($bag->merge(['class' => 'a b', 'style' => 'margin: 0', 'id' => 'default', 'role' => 'note'])->toHtml())
        ->toBe('class="a b a" style="margin: 0; color: red" id="mine" role="note"')
        ->and($bag->toHtml())->toBe('class="a" style="color: red" id="mine"');
});

it('renders attributes as html_attr() does', function (array $attributes, string $html): void {
    expect(new ComponentAttributeBag($attributes)->toHtml())->toBe($html);
})->with([
    'a backed enum, as its value' => [['size' => IconSize::Medium], 'size="md"'],
    'aria- booleans, as true and false' => [['aria-expanded' => true, 'aria-hidden' => false], 'aria-expanded="true" aria-hidden="false"'],
    'a data- true, and a data- array as JSON' => [['data-open' => true, 'data-config' => ['a' => 1]], 'data-open="true" data-config="{&quot;a&quot;:1}"'],
    'true as an empty value, null and false dropped' => [['hidden' => true, 'title' => null, 'disabled' => false], 'hidden=""'],
    'an iterable on spaces, null and false dropped' => [['rel' => ['noopener', null, false, 'noreferrer']], 'rel="noopener noreferrer"'],
    'an iterable holding only true, as an empty value' => [['controls' => [true]], 'controls=""'],
    'an iterable holding only null and false, dropped' => [['rel' => [null, false], 'id' => 'kept'], 'id="kept"'],
    'a hostile name, escaped' => [['onclick="x"' => 'y'], 'onclick&#x3D;&quot;x&quot;="y"'],
    'a name with an invalid byte, escaped byte by byte' => [["a\xFFb" => 'y'], '&#x61;&#xFF;&#x62;="y"'],
    'a value with an invalid byte, substituted' => [['title' => "a\xFFb"], "title=\"a\u{FFFD}b\""],
]);
