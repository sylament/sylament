<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Sylament\Schemas\State\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;

function accessor(): PropertyAccessor
{
    return new PropertyAccessor(PropertyAccess::createPropertyAccessor());
}

it('reads a nested value by dotted path (dot to bracket)', function (): void {
    $host = new class {
        public array $data = ['address' => ['city' => 'Prague']];
    };

    expect(accessor()->get($host, 'data.address.city'))->toBe('Prague');
});

it('returns null for a missing index without throwing', function (): void {
    $host = new class {
        public array $data = [];
    };

    expect(accessor()->get($host, 'data.missing'))->toBeNull();
});

it('writes the whole bag on a single-segment path', function (): void {
    $host = new class {
        public array $data = [];
    };

    accessor()->set($host, 'data', ['title' => 'Hi']);

    expect($host->data)->toBe(['title' => 'Hi']);
});

it('auto-vivifies nested arrays on write', function (): void {
    $host = new class {
        public array $data = [];
    };

    accessor()->set($host, 'data.address.city', 'Brno');

    expect($host->data)->toBe(['address' => ['city' => 'Brno']]);
});

it('reads a detached nested array by dotted path, null when absent', function (): void {
    $state = ['a' => ['b' => 'deep']];

    expect(accessor()->getFromArray($state, 'a.b'))->toBe('deep')
        ->and(accessor()->getFromArray($state, 'a.missing'))->toBeNull()
        ->and(accessor()->getFromArray($state, 'a.b.past-scalar'))->toBeNull();
});

it('writes into a detached nested array, auto-vivifying intermediates', function (): void {
    $state = [];

    accessor()->setInArray($state, 'a.b.c', 1);

    expect($state)->toBe(['a' => ['b' => ['c' => 1]]]);
});

it('clobbers a non-array intermediate on write', function (): void {
    $state = ['a' => 'scalar'];

    accessor()->setInArray($state, 'a.b', 2);

    expect($state)->toBe(['a' => ['b' => 2]]);
});

it('forgets only the leaf, leaving the emptied parent behind', function (): void {
    $state = ['a' => ['b' => 1]];

    accessor()->forgetFromArray($state, 'a.b');

    expect($state)->toBe(['a' => []]);
});

it('forgets a list of paths and no-ops on a missing path', function (): void {
    $state = ['a' => 1, 'b' => 2, 'c' => 3];

    accessor()->forgetFromArray($state, ['a', 'c', 'x.y']);

    expect($state)->toBe(['b' => 2]);
});
