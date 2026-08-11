<?php

declare(strict_types=1);

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\BaseFilter;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\SchemaTableHost;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Filters\DefaultNameFilter;

it('reads one filter slice out of the applied bag and tolerates junk shapes', function (): void {
    $host = new TableHost(static fn (Table $table): Table => $table->filters([Filter::make('isPublished')]));

    $host->tableFilters = [
        'isPublished' => ['isActive' => true],
        'broken' => 'not-an-array',
    ];

    expect($host->getTableFilterState('isPublished'))->toBe(['isActive' => true])
        ->and($host->getTableFilterState('broken'))->toBeNull()
        ->and($host->getTableFilterState('absent'))->toBeNull();
});

it('reads the form state out of the deferred bag while filters are deferred, and the applied bag otherwise', function (): void {
    $host = new TableHost(static fn (Table $table): Table => $table->filters([Filter::make('isPublished')]));

    $host->tableFilters = ['isPublished' => ['isActive' => false]];
    $host->tableDeferredFilters = ['isPublished' => ['isActive' => true]];

    expect($host->getTableFilterFormState('isPublished'))->toBe(['isActive' => true]);

    $live = new TableHost(static fn (Table $table): Table => $table
        ->filters([Filter::make('isPublished')])
        ->deferFilters(false));

    $live->tableFilters = ['isPublished' => ['isActive' => false]];
    $live->tableDeferredFilters = ['isPublished' => ['isActive' => true]];

    expect($live->getTableFilterFormState('isPublished'))->toBe(['isActive' => false]);
});

it('resolves a filter class-string to its default name and passes anything else through', function (): void {
    $host = new TableHost();

    expect($host->parseTableFilterName('isPublished'))->toBe('isPublished')
        ->and($host->parseTableFilterName(DefaultNameFilter::class))->toBe('defaultNamed')
        ->and($host->parseTableFilterName(Filter::class))->toBe(Filter::class)
        ->and(Filter::getDefaultName())->toBeNull()
        ->and(is_a(DefaultNameFilter::class, BaseFilter::class, allow_string: true))->toBeTrue();
});

it('refuses to build the filters form on a host that is not a schema host', function (): void {
    $host = new TableHost(static fn (Table $table): Table => $table->filters([Filter::make('isPublished')]));

    expect(static fn (): object => $host->getTableFiltersForm())
        ->toThrow(LogicException::class, InteractsWithSchemas::class);

    expect(static fn (): object => $host->getTableFiltersForm())
        ->toThrow(LogicException::class, HasSchemas::class);
});

it('builds an empty filters form for a schema host whose table declares no filters', function (): void {
    $host = new SchemaTableHost();

    expect($host->getTableFiltersForm()->getComponents())->toBe([])
        ->and($host->getTableFiltersForm()->getKey())->toBe('tableFiltersForm');
});

it('declares the filters form on the table host contract', function (): void {
    expect(method_exists(InteractsWithTable::class, 'getTableFiltersForm'))->toBeTrue();
});
