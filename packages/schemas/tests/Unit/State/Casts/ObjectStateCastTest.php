<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use LogicException;
use Sylament\Schemas\State\Casts\ObjectStateCast;
use Sylament\Schemas\Tests\Fixtures\State\Casts\AbstractLine;
use Sylament\Schemas\Tests\Fixtures\State\Casts\PromotedLine;
use Sylament\Schemas\Tests\Fixtures\State\Casts\SetterLine;
use Sylament\Schemas\Tests\Fixtures\State\Casts\StrictLine;

it('constructs a promoted-constructor item from matching keys', function (): void {
    $cast = new ObjectStateCast(PromotedLine::class);

    $item = $cast->get(['title' => 'a', 'note' => 'b']);

    expect($item)->toBeInstanceOf(PromotedLine::class)
        ->and($item->title)->toBe('a')
        ->and($item->note)->toBe('b');
});

it('constructs a setter-based item through the accessor', function (): void {
    $cast = new ObjectStateCast(SetterLine::class);

    $item = $cast->get(['title' => 'a']);

    expect($item)->toBeInstanceOf(SetterLine::class)
        ->and($item->getTitle())->toBe('a');
});

it('ignores keys outside the name set silently', function (): void {
    $cast = new ObjectStateCast(PromotedLine::class);

    $item = $cast->get(['title' => 'a', 'legacy' => true, 'tampered' => ['x']]);

    expect($item)->toBeInstanceOf(PromotedLine::class)
        ->and($item->title)->toBe('a');
});

it('keeps a constructor default when the key is absent', function (): void {
    $item = new ObjectStateCast(PromotedLine::class)->get(['note' => 'b']);

    expect($item->title)->toBeNull()
        ->and($item->note)->toBe('b');
});

it('passes null through get untouched', function (): void {
    expect(new ObjectStateCast(PromotedLine::class)->get(null))->toBeNull();
});

it('passes an already-constructed item through get untouched', function (): void {
    $line = new PromotedLine(title: 'a');

    expect(new ObjectStateCast(PromotedLine::class)->get($line))->toBe($line);
});

it('refuses a non-array item state in get', function (): void {
    expect(fn (): mixed => new ObjectStateCast(PromotedLine::class)->get('scalar'))
        ->toThrow(LogicException::class, PromotedLine::class);
});

it('names the field-side fix when null meets a required constructor parameter', function (): void {
    expect(fn (): mixed => new ObjectStateCast(StrictLine::class)->get(['title' => null]))
        ->toThrow(LogicException::class, 'required()');
});

it('names the fix when a required constructor parameter has no key at all', function (): void {
    expect(fn (): mixed => new ObjectStateCast(StrictLine::class)->get([]))
        ->toThrow(LogicException::class, '$title');
});

it('deconstructs a promoted item over its name set', function (): void {
    $values = new ObjectStateCast(PromotedLine::class)->set(new PromotedLine(title: 'a', note: 'b'));

    expect($values)->toBe(['title' => 'a', 'note' => 'b']);
});

it('deconstructs a getter-backed item through the accessor', function (): void {
    $line = new SetterLine();
    $line->setTitle('a');

    expect(new ObjectStateCast(SetterLine::class)->set($line))->toBe(['title' => 'a']);
});

it('passes an array through set untouched', function (): void {
    expect(new ObjectStateCast(PromotedLine::class)->set(['title' => 'a']))->toBe(['title' => 'a']);
});

it('passes null through set untouched', function (): void {
    expect(new ObjectStateCast(PromotedLine::class)->set(null))->toBeNull();
});

it('refuses a foreign object in set, naming both classes', function (): void {
    expect(fn (): mixed => new ObjectStateCast(PromotedLine::class)->set(new SetterLine()))
        ->toThrow(LogicException::class, SetterLine::class);
});

it('refuses a scalar in set', function (): void {
    expect(fn (): mixed => new ObjectStateCast(PromotedLine::class)->set(42))
        ->toThrow(LogicException::class, PromotedLine::class);
});

it('lets a denormalize closure own construction', function (): void {
    $cast = new ObjectStateCast(
        PromotedLine::class,
        denormalizeUsing: static fn (array $item): PromotedLine => new PromotedLine(title: mb_strtoupper((string) $item['title'])),
    );

    expect($cast->get(['title' => 'a'])->title)->toBe('A');
});

it('lets a normalize closure own deconstruction', function (): void {
    $cast = new ObjectStateCast(
        PromotedLine::class,
        normalizeUsing: static fn (PromotedLine $item): array => ['title' => $item->title, 'extra' => true],
    );

    expect($cast->set(new PromotedLine(title: 'a')))->toBe(['title' => 'a', 'extra' => true]);
});

it('refuses a non-instantiable item class at construction', function (): void {
    expect(fn (): ObjectStateCast => new ObjectStateCast(AbstractLine::class))
        ->toThrow(LogicException::class, AbstractLine::class);
});

it('refuses a class that does not exist at construction', function (): void {
    expect(fn (): ObjectStateCast => new ObjectStateCast('App\\Missing\\Line'))
        ->toThrow(LogicException::class, 'App\\Missing\\Line');
});
