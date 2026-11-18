<?php

declare(strict_types=1);

use Sylament\Tables\Filters\Filter;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;

it('names its field and its chip by its label', function (): void {
    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)->filterTable('sealed');

    expect($tester->render()->crawler())
        ->toContainElement(Selectors::filterFieldLabel('sealed', 'isActive'), 'Sealed jars')
        ->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Sealed jars');
});

it('derives its label from its name when given none', function (string $name, string $label): void {
    expect(Filter::make($name)->getLabel())->toBe($label);
})->with([
    'snake case' => ['is_featured', 'Is featured'],
    'camel case' => ['sealedOn', 'Sealed on'],
    'a dotted name' => ['sealed.nested', 'Sealed'],
]);
