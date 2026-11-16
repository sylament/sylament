<?php

declare(strict_types=1);

use Sylament\Schemas\Validation\RecordKeyGuard;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('raises one violation per refusal at its path, and none for no refusal', function (array $refusals, array $expected): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate(new OrderLine(order: 1, line: 1), RecordKeyGuard::constraint($refusals));

    expect(array_map(
        static fn (ConstraintViolationInterface $violation): array => [$violation->getPropertyPath(), (string) $violation->getMessage()],
        iterator_to_array($violations),
    ))->toBe($expected);
})->with([
    'two refusals' => [
        ['order' => 'This value is already used.', 'line' => 'This value cannot be changed.'],
        [['order', 'This value is already used.'], ['line', 'This value cannot be changed.']],
    ],
    'no refusal' => [[], []],
]);
