<?php

declare(strict_types=1);

use Sylament\Support\Pagination\Paginator;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\GroupedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\ReorderableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('labels a record as a record, and many as records, on a table with no record class', function (): void {
    $table = $this->createLiveComponent(AlignedColumnsTable::class)->component()->getTable();

    expect($table->getRecordLabel())->toBe('record')
        ->and($table->getPluralRecordLabel())->toBe('records');
});

it('names its records by the label given, the plural deriving from it until one is given', function (array $props, string $heading): void {
    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class, $props)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::EMPTY_STATE . ' h3', $heading)
        ->and($crawler->filter(Selectors::EMPTY_STATE . ' p')->text())->toBe('Create a jar to get started.');
})->with([
    'a given label' => [['recordLabel' => 'jar'], 'No jars'],
    'a given plural' => [['recordLabel' => 'jar', 'pluralRecordLabel' => 'jarfuls'], 'No jarfuls'],
]);

it("titles a record by the closure, else by the title attribute, and by the table's label wherever the attribute reads no text", function (Closure $configure, string $title): void {
    $table = $configure($this->createLiveComponent(HeadedLarderJarTable::class)->component()->getTable());
    $jar = new LarderJar('Apricot');
    $jar->larder = new Larder('Cellar');

    expect($table->getRecordTitle($jar))->toBe($title);
})->with([
    'a title closure' => [fn (Table $table): Table => $table->recordTitleAttribute('label')->recordTitle(static fn (LarderJar $jar): string => "Jar of {$jar->label}"), 'Jar of Apricot'],
    'a title attribute' => [fn (Table $table): Table => $table->recordTitleAttribute('label'), 'Apricot'],
    'a missing property' => [fn (Table $table): Table => $table->recordTitleAttribute('shelf'), 'larder jar'],
    'a null column' => [fn (Table $table): Table => $table->recordTitleAttribute('sealedOn'), 'larder jar'],
    'an object column' => [fn (Table $table): Table => $table->recordTitleAttribute('larder'), 'larder jar'],
    'no title at all' => [fn (Table $table): Table => $table, 'larder jar'],
]);

it("hands back the current page keyed by record key, and the page's carrier only on a paginated table", function (): void {
    $this->seed(...array_map(static fn (int $position): LarderJar => new LarderJar("Jar {$position}", position: $position), range(1, 6)));

    $paginated = $this->createLiveComponent(GroupedLarderJarTable::class)->component()->getTable();
    $unpaginated = $this->createLiveComponent(ReorderableLarderJarTable::class)->call('toggleTableReordering')->component()->getTable();

    expect(array_map(static fn (object|array $jar): string => $jar instanceof LarderJar ? $jar->label : 'not a jar', $paginated->getRecords()))
        ->toBe(['1' => 'Jar 1', '2' => 'Jar 2', '3' => 'Jar 3', '4' => 'Jar 4', '5' => 'Jar 5'])
        ->and($paginated->getPaginator())->toBeInstanceOf(Paginator::class)
        ->and($paginated->getPaginator()?->getTotalRecordCount())->toBe(6)
        ->and($unpaginated->getRecords())->toHaveCount(6)
        ->and($unpaginated->getPaginator())->toBeNull();
});
