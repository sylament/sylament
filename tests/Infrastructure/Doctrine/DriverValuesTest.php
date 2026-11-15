<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Infrastructure\Doctrine\DriverValues;

it('reads an aggregate as the same numbers on every platform', function (): void {
    $two = new Tally();
    $two->count = 2;

    $three = new Tally();
    $three->count = 3;
    $this->seed($two, $three);

    $row = $this->entityManager()->getConnection()->fetchAssociative('SELECT SUM(count) AS total, AVG(count) AS mean FROM Tally');

    expect(DriverValues::numeric($row ?: []))->toBe(['total' => 5, 'mean' => 2.5]);
});

it('turns a numeric string into the number it spells and leaves any other value as it is', function (mixed $value, mixed $expected): void {
    expect(DriverValues::numeric(['value' => $value]))->toBe(['value' => $expected]);
})->with([
    'an integer string' => ['-3', -3],
    'a decimal string' => ['82.5000000000000000', 82.5],
    'a native number' => [7, 7],
    'text' => ['ten', 'ten'],
    'null' => [null, null],
]);
