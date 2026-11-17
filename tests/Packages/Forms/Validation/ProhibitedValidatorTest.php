<?php

declare(strict_types=1);

use Sylament\Forms\Validation\Prohibited;
use Sylament\Forms\Validation\RequiredValue;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('refuses a present value, false and zero included, exactly where a required value passes', function (mixed $value, bool $present): void {
    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $violations = $validator->validate($value, new Prohibited());

    expect(count($violations))->toBe($present ? 1 : 0)
        ->and(count($validator->validate($value, new RequiredValue())))->toBe($present ? 0 : 1);

    if ($present) {
        expect($violations->get(0)->getMessage())->toBe('This value is prohibited.');
    }
})->with([
    'null' => [null, false],
    'an empty string' => ['', false],
    'whitespace-only text' => ['   ', false],
    'an empty list, what a CheckboxList with nothing ticked holds' => [[], false],
    'false' => [false, true],
    'zero' => [0, true],
    'text' => ['x', true],
]);
