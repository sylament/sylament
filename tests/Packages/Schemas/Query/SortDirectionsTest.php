<?php

declare(strict_types=1);

use Sylament\Schemas\Query\SortDirections;

it("turns asc or desc, in any case, into the direction Doctrine's builder takes", function (string $direction, SortDirection $expected): void {
    expect(SortDirections::from($direction))->toBe($expected);
})->with([
    'lower case' => ['asc', SortDirection::Ascending],
    'upper case' => ['DESC', SortDirection::Descending],
    'mixed case' => ['Asc', SortDirection::Ascending],
]);

it('refuses anything but asc or desc by name, never defaulting to ascending', function (string $direction): void {
    expect(fn (): SortDirection => SortDirections::from($direction))
        ->toThrow(InvalidArgumentException::class, sprintf('Invalid sort direction "%s": expected asc or desc.', $direction));
})->with([
    'a direction with a nulls clause' => ['DESC NULLS LAST'],
    'another word' => ['up'],
]);
