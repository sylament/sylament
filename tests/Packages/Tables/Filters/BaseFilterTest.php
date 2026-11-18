<?php

declare(strict_types=1);

use Sylament\Tables\Filters\Filter;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\IndicatingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\QueryingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\SealedJarFilter;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;

it('refuses to be made without a name, naming its class', function (?string $name): void {
    expect(fn (): Filter => Filter::make($name))
        ->toThrow(LogicException::class, 'Filter of class [' . Filter::class . '] must have a unique name, passed to the [make()] method.');
})->with([
    'no name' => [null],
    'a blank name' => [''],
]);

it('is made by its default name when given none, then runs the configurations registered for it', function (): void {
    SealedJarFilter::configureUsing(static fn (SealedJarFilter $filter): SealedJarFilter => $filter->label('Configured seal'));

    expect($this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler())
        ->toContainElement(Selectors::filterFieldLabel('sealed', 'isActive'), 'Configured seal');
});

it('counts one active filter per chip it shows', function (): void {
    $tester = $this->createLiveComponent(IndicatingJarFilterTable::class);

    expect($tester->render()->crawler())->toContainElement(Selectors::FILTERS_TRIGGER_BADGE, '4');

    $tester->filterTable('fielded');

    expect($tester->render()->crawler())->toContainElement(Selectors::FILTERS_TRIGGER_BADGE, '5');
});

it('hands its callbacks the filter, the live host and the table by name', function (): void {
    $this->seed(new LarderJar('Plum', position: 1));

    $this->createLiveComponent(QueryingJarFilterTable::class)->filterTable('injected');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toContain('injected on ' . QueryingJarFilterTable::class . ', its own table');
});
