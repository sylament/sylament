<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\ObjectStateCast;
use Sylament\Tests\Fixtures\Schemas\Record\AbstractParcel;
use Sylament\Tests\Fixtures\Schemas\Record\ParcelLabel;

it('builds a fresh item from an item array, by constructor parameter and then by writable property', function (): void {
    $cast = new ObjectStateCast(ParcelLabel::class);
    $item = ['name' => 'box', 'copies' => 2, 'colour' => 'red', 'fragile' => true, 'stack' => true];

    $label = $cast->get($item);

    expect($label)->toBeInstanceOf(ParcelLabel::class)
        ->and([$label->name, $label->copies, $label->colour, $label->isFragile(), $label->canStack()])->toBe(['box', 2, 'red', true, true])
        ->and($cast->get($item))->not->toBe($label);
});

it('fills a missing key from its default, keeps an explicit null a nullable parameter takes, and the default a non-nullable one keeps', function (): void {
    $cast = new ObjectStateCast(ParcelLabel::class);

    expect($cast->get(['name' => 'box'])->note)->toBe('draft')
        ->and($cast->get(['name' => 'box', 'note' => null])->note)->toBeNull()
        ->and($cast->get(['name' => 'box', 'copies' => null])->copies)->toBe(1);
});

it('deconstructs an item over its constructor parameters, public properties and accessors', function (): void {
    $label = new ParcelLabel('box', copies: 2);
    $label->colour = 'red';
    $label->setFragile(true);
    $label->setStack(true);
    $label->assignTrackingCode('T-1');

    expect(new ObjectStateCast(ParcelLabel::class)->set($label))->toMatchArray([
        'name' => 'box',
        'note' => 'draft',
        'copies' => 2,
        'colour' => 'red',
        'summary' => 'box x2',
        'fragile' => true,
        'tracking' => true,
        'stack' => true,
    ]);
});

it('round-trips every name both directions reach, and drops state outside them', function (): void {
    $cast = new ObjectStateCast(ParcelLabel::class);
    $label = new ParcelLabel('box');
    $label->setFragile(true);
    $label->setStack(true);
    $label->assignTrackingCode('T-1');

    $rebuilt = $cast->get($cast->set($label));

    expect([$rebuilt->name, $rebuilt->isFragile(), $rebuilt->canStack()])->toBe(['box', true, true])
        ->and($rebuilt->trackingCode())->toBeNull();
});

it('ignores an item key that names no writable property, and never writes through a path', function (): void {
    $label = new ObjectStateCast(ParcelLabel::class)->get([
        'name' => 'box',
        'unknown' => 1,
        0 => 'indexed',
        '' => 'empty',
        'address[' => 'unparseable',
        'address.city' => 'Košice',
        'address[city]' => 'Nitra',
    ]);

    expect($label->name)->toBe('box')
        ->and($label->address->city)->toBe('Bratislava');
});

it('converts through the closures given for either half instead of the item class', function (): void {
    $denormalizes = new ObjectStateCast(ParcelLabel::class, denormalizeUsing: fn (array $row): ParcelLabel => new ParcelLabel(mb_strtoupper((string) $row['label'])));
    $normalizes = new ObjectStateCast(ParcelLabel::class, normalizeUsing: fn (ParcelLabel $label): array => ['label' => $label->name]);

    expect($denormalizes->get(['label' => 'box'])->name)->toBe('BOX')
        ->and($denormalizes->set(new ParcelLabel('box')))->toMatchArray(['name' => 'box'])
        ->and($normalizes->set(new ParcelLabel('box')))->toBe(['label' => 'box'])
        ->and($normalizes->get(['name' => 'box'])->name)->toBe('box');
});

it('refuses what it cannot convert, naming the class and the fix', function (Closure $convert, string $message): void {
    expect($convert)->toThrow(LogicException::class, $message);
})->with([
    'a missing item class' => [fn (): ObjectStateCast => new ObjectStateCast('Sylament\Tests\Fixtures\Schemas\Record\MissingParcel'), 'needs an instantiable item class and "Sylament\Tests\Fixtures\Schemas\Record\MissingParcel" is not one'],
    'an abstract item class, closures given' => [fn (): ObjectStateCast => new ObjectStateCast(AbstractParcel::class, fn (array $row): object => new stdClass(), fn (object $item): array => []), 'needs an instantiable item class and "' . AbstractParcel::class . '" is not one'],
    'a scalar item to build' => [fn (): mixed => new ObjectStateCast(ParcelLabel::class, denormalizeUsing: fn (array $row): ParcelLabel => new ParcelLabel('box'))->get('box'), 'ObjectStateCast over ' . ParcelLabel::class . ' met a string item on dehydration'],
    'a foreign object to deconstruct' => [fn (): mixed => new ObjectStateCast(ParcelLabel::class)->set(new stdClass()), 'ObjectStateCast over ' . ParcelLabel::class . ' met a stdClass item on fill'],
    'a scalar to deconstruct' => [fn (): mixed => new ObjectStateCast(ParcelLabel::class)->set(5), 'met a int item on fill'],
    'null for a required parameter' => [fn (): mixed => new ObjectStateCast(ParcelLabel::class)->get(['name' => null]), 'constructor parameter $name is non-optional and the dehydrated item supplies null for it (item keys: name). Declare required() on the field that feeds it'],
    'a value a property rejects' => [fn (): mixed => new ObjectStateCast(ParcelLabel::class)->get(['name' => 'box', 'weight' => 'heavy']), 'property "weight" rejects the dehydrated value (string). Declare required() on the field that feeds it'],
]);

it('passes null and a plain item array through untouched', function (): void {
    $cast = new ObjectStateCast(ParcelLabel::class);

    expect($cast->get(null))->toBeNull()
        ->and($cast->set(null))->toBeNull()
        ->and($cast->set(['name' => 'box']))->toBe(['name' => 'box']);
});
