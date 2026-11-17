<?php

declare(strict_types=1);

use Sylament\Forms\Validation\Distinct;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it("answers by the value's type: a scalar a sibling holds loosely, a list any sibling's list meets, a second ticked box, and no ticked box while one is required", function (mixed $value, Distinct $constraint, ?string $message): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate($value, $constraint);

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($message === null ? [] : [$message]);
})->with([
    'a scalar a sibling holds in another spelling' => ['1', new Distinct(mode: Distinct::SCALAR, siblingValues: [1, 'b']), 'This value is already used by another item.'],
    'a scalar no sibling holds' => ['a', new Distinct(mode: Distinct::SCALAR, siblingValues: ['b']), null],
    'a list meeting a sibling list' => [['a', 'b'], new Distinct(mode: Distinct::ARRAY, siblingValues: ['c', 'b']), 'This value is already used by another item.'],
    'a list no sibling list meets, an uncastable member ignored' => [['a', ['b']], new Distinct(mode: Distinct::ARRAY, siblingValues: ['c', ['b']]), null],
    'a scalar under the array mode' => ['a', new Distinct(mode: Distinct::ARRAY, siblingValues: ['a']), null],
    'a ticked box beside a ticked sibling' => [true, new Distinct(mode: Distinct::BOOLEAN, isSiblingSelected: true), 'Only one item may be selected.'],
    'the one ticked box' => [true, new Distinct(mode: Distinct::BOOLEAN), null],
    'no ticked box while one is required' => [false, new Distinct(mode: Distinct::BOOLEAN, mustBeSelected: true), 'At least one item must be selected.'],
    'no ticked box while none is required' => [false, new Distinct(mode: Distinct::BOOLEAN), null],
    'a blank value' => ['', new Distinct(mode: Distinct::SCALAR, siblingValues: ['']), null],
]);
