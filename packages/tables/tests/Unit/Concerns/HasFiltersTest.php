<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\BaseFilter;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Filters\Indicator;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\SchemaTableHost;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song as DoctrineSong;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
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

function filterableSongs(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new DoctrineSong(1, 'Alpha One', 10),
        new DoctrineSong(2, 'Alpha Two', 20),
        new DoctrineSong(3, 'Beta Three', 30),
        new DoctrineSong(4, 'Beta Four', 40),
        new DoctrineSong(5, 'Beta Five', 50),
    );

    return $entityManager;
}

function filtersHost(Closure $configure): SchemaTableHost
{
    $entityManager = filterableSongs();

    return new SchemaTableHost(fn (Table $table): Table => $configure(
        $table->query($entityManager->createQueryBuilder()->select('song')->from(DoctrineSong::class, 'song')),
    ));
}

it('applies the deferred bag, resets the page and flushes the record cache', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        Filter::make('featured'),
    ]));

    $host->getTableFiltersForm();
    $host->tablePage = 4;
    $host->tableDeferredFilters = ['featured' => ['isActive' => true]];

    expect($host->getTableRecords()->getRecords())->toBe([]);

    $host->applyTableFilters();

    expect($host->tableFilters)->toBe(['featured' => ['isActive' => true]])
        ->and($host->tablePage)->toBe(1)
        ->and($host->getTableRecords()->getRecords())->toHaveCount(5);
});

it('narrows a chip removal to the named field', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        Filter::make('window')->schema([
            TextInput::make('from'),
            TextInput::make('until'),
        ]),
    ]));

    $host->getTableFiltersForm();
    $host->tableDeferredFilters = ['window' => ['from' => '2020', 'until' => '2026']];

    $host->removeTableFilter('window', 'from');

    expect($host->tableDeferredFilters)->toBe(['window' => ['from' => null, 'until' => '2026']])
        ->and($host->tableFilters)->toBe($host->tableDeferredFilters);
});

it('resets every field of a filter when no field is named', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        Filter::make('window')->schema([
            TextInput::make('from'),
            TextInput::make('until'),
        ]),
    ]));

    $host->getTableFiltersForm();
    $host->tableDeferredFilters = ['window' => ['from' => '2020', 'until' => '2026']];

    $host->removeTableFilter('window');

    expect($host->tableDeferredFilters)->toBe(['window' => ['from' => null, 'until' => null]]);
});

it('derives a reset value from the field state type when the filter names none', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        Filter::make('mixed')->schema([
            TextInput::make('term'),
            Checkbox::make('flag'),
            Select::make('tags')->multiple()->options(['a' => 'A', 'b' => 'B']),
        ]),
    ]));

    $host->getTableFiltersForm();
    $host->tableDeferredFilters = ['mixed' => ['term' => 'x', 'flag' => true, 'tags' => ['a']]];

    $host->removeTableFilter('mixed');

    expect($host->tableDeferredFilters)->toBe(['mixed' => ['term' => null, 'flag' => false, 'tags' => []]]);
});

it('prefers the filters own reset state over the type-derived fallback', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ]));

    $host->getTableFiltersForm();
    $host->tableDeferredFilters = ['status' => ['value' => 'draft']];

    $host->removeTableFilter('status');

    expect($host->tableDeferredFilters)->toBe(['status' => ['value' => null]]);
});

it('skips a filter whose indicator refuses removal when removing them all', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        Filter::make('pinned')->indicateUsing(fn (): array => [Indicator::make('Pinned')->removable(false)]),
        Filter::make('featured'),
    ]));

    $host->getTableFiltersForm();
    $host->tableDeferredFilters = ['pinned' => ['isActive' => true], 'featured' => ['isActive' => true]];
    $host->tableFilters = $host->tableDeferredFilters;

    $host->removeTableFilters();

    expect($host->tableFilters)->toBe(['pinned' => ['isActive' => true], 'featured' => ['isActive' => false]]);
});

it('clears the search and the page alongside every removable filter', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([Filter::make('featured')]));

    $host->getTableFiltersForm();
    $host->tableSearch = 'beta';
    $host->tablePage = 3;
    $host->tableDeferredFilters = ['featured' => ['isActive' => true]];

    $host->removeTableFilters();

    expect($host->tableSearch)->toBe('')
        ->and($host->tablePage)->toBe(1)
        ->and($host->tableFilters)->toBe(['featured' => ['isActive' => false]]);
});

it('restores field defaults rather than reset state when the form is reset', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        Filter::make('featured')->default(),
    ]));

    $host->getTableFiltersForm();
    $host->tableDeferredFilters = ['featured' => ['isActive' => false]];

    $host->resetTableFiltersForm();

    expect($host->tableDeferredFilters)->toBe(['featured' => ['isActive' => true]])
        ->and($host->tableFilters)->toBe(['featured' => ['isActive' => true]]);
});

it('mirrors a direct applied write back into the deferred bag', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([Filter::make('featured')]));

    $host->tablePage = 6;
    $host->tableFilters = ['featured' => ['isActive' => true]];

    $host->updatedTableFilters([]);

    expect($host->tableDeferredFilters)->toBe(['featured' => ['isActive' => true]])
        ->and($host->tablePage)->toBe(1);
});

it('ignores an unchanged applied bag', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([Filter::make('featured')]));

    $host->tablePage = 6;
    $host->tableFilters = ['featured' => ['isActive' => true]];

    $host->updatedTableFilters(['featured' => ['isActive' => true]]);

    expect($host->tableDeferredFilters)->toBe([])
        ->and($host->tablePage)->toBe(6);
});

it('clears the search term and the page through resetTableSearch', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table);

    $host->tableSearch = 'beta';
    $host->tablePage = 5;

    $host->resetTableSearch();

    expect($host->tableSearch)->toBe('')
        ->and($host->tablePage)->toBe(1);
});

it('overwrites a mount-passed bag wherever the field declares a default', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([
        Filter::make('featured'),
        SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ]));

    $host->tableDeferredFilters = ['featured' => ['isActive' => true], 'status' => ['value' => 'draft']];
    $host->tableFilters = $host->tableDeferredFilters;

    $host->mountTableFilters([]);

    expect($host->tableDeferredFilters)->toBe(['featured' => ['isActive' => false], 'status' => ['value' => 'draft']])
        ->and($host->tableFilters)->toBe($host->tableDeferredFilters);
});

it('resets the page from a field hook in live mode', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table
        ->deferFilters(false)
        ->filters([Filter::make('featured')]));

    $host->tablePage = 7;

    foreach ($host->getTableFiltersForm()->getFlatComponents(withActions: false) as $component) {
        $component->callAfterStateUpdatedHooks();
    }

    expect($host->tablePage)->toBe(1);
});

it('keys the live-mode form and its fields after the hook walk that resolves them early', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table
        ->deferFilters(false)
        ->filters([Filter::make('featured')]));

    $form = $host->getTableFiltersForm();

    $keys = array_map(
        static fn (Component $component): ?string => $component->getKey(),
        $form->getFlatComponents(withActions: false),
    );

    expect($form->getKey())->toBe('tableFiltersForm')
        ->and($keys)->toBe(['tableFiltersForm.featured', 'tableFiltersForm.featured.isActive']);
});

it('attaches no field hook in deferred mode', function (): void {
    $host = filtersHost(fn (Table $table): Table => $table->filters([Filter::make('featured')]));

    $host->tablePage = 7;

    foreach ($host->getTableFiltersForm()->getFlatComponents(withActions: false) as $component) {
        $component->callAfterStateUpdatedHooks();
    }

    expect($host->tablePage)->toBe(7);
});
