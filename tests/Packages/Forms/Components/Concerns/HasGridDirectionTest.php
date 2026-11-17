<?php

declare(strict_types=1);

use Sylament\Forms\Components\CheckboxList;
use Sylament\Support\Enums\GridDirection;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('flows the options down the columns for Column and across them for Row, given the case or its string', function (GridDirection|string $direction, bool $isColumnWise): void {
    $grid = $this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => 'Fern'])->gridDirection($direction))->filter(Selectors::CHECKBOX_LIST_OPTIONS);

    expect($grid->count())->toBe(1)
        ->and($grid->matches('.sy-grid-direction-col'))->toBe($isColumnWise);
})->with([
    'the Column case' => [GridDirection::Column, true],
    'the col string' => ['col', true],
    'the Row case' => [GridDirection::Row, false],
    'the row string' => ['row', false],
]);

it("lands on the field's own Column default when no direction is set or the string names none, throwing nothing", function (?string $direction): void {
    $list = CheckboxList::make('tags')->options(['fern' => 'Fern']);

    if ($direction !== null) {
        $list->gridDirection($direction);
    }

    expect($this->renderSchemaComponent($list))->toContainElement(Selectors::CHECKBOX_LIST_OPTIONS . '.sy-grid-direction-col');
})->with([
    'no direction' => [null],
    'a string naming no direction' => ['diagonal'],
    'an empty string' => [''],
]);
