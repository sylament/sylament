<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig;

use Stringable;
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

    $bag = new ComponentAttributeBag(['data-a' => $stringable, 'data-b' => ['not', 'stringable']]);

    expect($bag->toHtml())->toBe('data-a="stringable"');
});
