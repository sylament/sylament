<?php

declare(strict_types=1);

use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Symfony\Component\DomCrawler\Crawler;

it('reads the state it applies apart from the draft its form holds while the table defers its filters', function (): void {
    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)->set('tableDeferredFilters.drafted.isActive', true);

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Applied off, drafted on']);

    $tester->call('applyTableFilters');

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Applied on, drafted on']);
});

it('refuses a host that keeps no filter state, naming the trait that does', function (string $reader): void {
    $filter = Filter::make('sealed')->table(Table::make($this->createStub(InteractsWithTable::class)));

    expect(fn (): array => $filter->{$reader}())
        ->toThrow(LogicException::class, 'provides no filter state: it must compose [' . HasTable::class . '].');
})->with([
    'the applied state' => ['getState'],
    'the draft' => ['getFormState'],
]);
