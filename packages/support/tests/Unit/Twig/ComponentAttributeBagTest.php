<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig;

use stdClass;
use Stringable;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Twig\ComponentAttributeBag;

it('holds no attributes by default', function (): void {
    expect(new ComponentAttributeBag()->getAttributes())->toBe([]);
});

it('normalizes a class string into a whitespace-split token list', function (): void {
    $bag = new ComponentAttributeBag(['class' => "sy  sy-section\n custom"]);

    expect($bag->getAttributes())->toBe(['class' => ['sy', 'sy-section', 'custom']]);
});

it('resolves a conditional class map to its truthy keys', function (): void {
    $bag = new ComponentAttributeBag(['class' => ['always', 'on' => true, 'off' => false, ['nested other']]]);

    expect($bag->getAttributes())->toBe(['class' => ['always', 'on', 'nested', 'other']]);
});

it('normalizes a style string into a declaration list', function (): void {
    $bag = new ComponentAttributeBag(['style' => 'color: red; margin: 0 ;']);

    expect($bag->getAttributes())->toBe(['style' => ['color: red', 'margin: 0']]);
});

it('resolves a style map to property-value declarations', function (): void {
    $bag = new ComponentAttributeBag(['style' => ['color' => 'red', '--sy-gap: 1rem']]);

    expect($bag->getAttributes())->toBe(['style' => ['color: red', '--sy-gap: 1rem']]);
});

it('does not split a style declaration on a semicolon inside a url() or quotes', function (): void {
    $bag = new ComponentAttributeBag([
        'style' => 'background: url(data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=); content: "a;b"',
    ]);

    expect($bag->getAttributes())->toBe(['style' => [
        'background: url(data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=)',
        'content: "a;b"',
    ]]);
});

it('keeps other attribute values untouched', function (): void {
    $bag = new ComponentAttributeBag(['data-owner' => 'billing', 'aria-hidden' => true]);

    expect($bag->getAttributes())->toBe(['data-owner' => 'billing', 'aria-hidden' => true]);
});

it('merge appends class tokens with defaults first', function (): void {
    $bag = new ComponentAttributeBag(['class' => 'existing'])->merge(['class' => 'default']);

    expect($bag->getAttributes())->toBe(['class' => ['default', 'existing']]);
});

it('merge appends style declarations with defaults first', function (): void {
    $bag = new ComponentAttributeBag(['style' => 'color: red'])->merge(['style' => 'margin: 0']);

    expect($bag->getAttributes())->toBe(['style' => ['margin: 0', 'color: red']]);
});

it('merge keeps the existing value for a colliding non-appendable key', function (): void {
    $bag = new ComponentAttributeBag(['data-owner' => 'existing'])->merge(['data-owner' => 'default']);

    expect($bag->getAttributes())->toBe(['data-owner' => 'existing']);
});

it('merge adds keys absent from the existing attributes', function (): void {
    $bag = new ComponentAttributeBag(['data-a' => '1'])->merge(['data-b' => '2']);

    expect($bag->getAttributes())->toBe(['data-b' => '2', 'data-a' => '1']);
});

it('merge returns a new instance and leaves the original untouched', function (): void {
    $original = new ComponentAttributeBag(['class' => 'existing']);
    $merged = $original->merge(['class' => 'default']);

    expect($merged)->not->toBe($original)
        ->and($original->getAttributes())->toBe(['class' => ['existing']]);
});

it('toHtml renders an empty bag as an empty string', function (): void {
    expect(new ComponentAttributeBag()->toHtml())->toBe('');
});

it('toHtml joins class tokens with spaces and style declarations with semicolons', function (): void {
    $bag = new ComponentAttributeBag([
        'class' => ['sy-icon', 'sy-size-md'],
        'style' => ['color' => 'red', 'margin' => '0'],
        'data-owner' => 'billing',
    ]);

    expect($bag->toHtml())->toBe('class="sy-icon sy-size-md" style="color: red; margin: 0" data-owner="billing"');
});

it('toHtml escapes attribute names and values', function (): void {
    $bag = new ComponentAttributeBag(['data-x"y' => 'a "quoted" <value>']);

    expect($bag->toHtml())->toBe('data-x&quot;y="a &quot;quoted&quot; &lt;value&gt;"');
});

it('toHtml entity-escapes a hostile attribute name so it cannot inject a handler', function (): void {
    $bag = new ComponentAttributeBag(['x onmouseover=alert(1)' => 'v']);

    expect($bag->toHtml())->toBe('x&#x20;onmouseover&#x3D;alert&#x28;1&#x29;="v"')
        ->and($bag->toHtml())->not->toContain(' onmouseover=');
});

it('toHtml substitutes invalid UTF-8 in a value instead of dropping the attribute', function (): void {
    $bag = new ComponentAttributeBag(['title' => "a\xB1b"]);

    expect($bag->toHtml())->toBe("title=\"a\u{FFFD}b\"");
});

it('toHtml renders true as a bare attribute and drops null and false', function (): void {
    $bag = new ComponentAttributeBag(['required' => true, 'disabled' => false, 'id' => null]);

    expect($bag->toHtml())->toBe('required=""');
});

it('toHtml drops an empty class or style list', function (): void {
    $bag = new ComponentAttributeBag(['class' => [], 'style' => '', 'data-a' => '1']);

    expect($bag->toHtml())->toBe('data-a="1"');
});

it('toHtml renders stringable values and skips non-stringable ones', function (): void {
    $stringable = new class implements Stringable {
        public function __toString(): string
        {
            return 'stringable';
        }
    };

    $bag = new ComponentAttributeBag(['title' => $stringable, 'alt' => new stdClass()]);

    expect($bag->toHtml())->toBe('title="stringable"');
});

it('toHtml joins a generic iterable value into a space-separated token list', function (): void {
    $bag = new ComponentAttributeBag(['aria-labelledby' => ['heading-1', null, 'label-1', false]]);

    expect($bag->toHtml())->toBe('aria-labelledby="heading-1 label-1"');
});

it('toHtml omits a generic iterable holding only null and false entries', function (): void {
    $bag = new ComponentAttributeBag(['aria-labelledby' => [null, false], 'id' => 'kept']);

    expect($bag->toHtml())->toBe('id="kept"');
});

it('toHtml keeps the attribute with an empty value for an iterable holding only true', function (): void {
    $bag = new ComponentAttributeBag(['itemscope' => [true]]);

    expect($bag->toHtml())->toBe('itemscope=""');
});

it('toHtml renders aria booleans as literal true and false strings', function (): void {
    $bag = new ComponentAttributeBag(['aria-hidden' => true, 'aria-expanded' => false, 'aria-label' => null]);

    expect($bag->toHtml())->toBe('aria-hidden="true" aria-expanded="false"');
});

it('toHtml renders a data attribute true as a literal true string and drops false', function (): void {
    $bag = new ComponentAttributeBag(['data-active' => true, 'data-off' => false]);

    expect($bag->toHtml())->toBe('data-active="true"');
});

it('toHtml JSON-encodes non-scalar data attribute values', function (): void {
    $bag = new ComponentAttributeBag(['data-config' => ['a' => 1, 'b' => [2, 3]]]);

    expect($bag->toHtml())->toBe('data-config="{&quot;a&quot;:1,&quot;b&quot;:[2,3]}"');
});

it('toHtml collapses BackedEnum values to their backing value', function (): void {
    $bag = new ComponentAttributeBag(['data-size' => IconSize::Medium, 'title' => IconSize::Large]);

    expect($bag->toHtml())->toBe('data-size="md" title="lg"');
});
