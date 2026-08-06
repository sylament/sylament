<?php

declare(strict_types=1);

use Sylament\Support\Pagination\Paginator;

it('carries one page of records with its counts', function (): void {
    $paginator = new Paginator(records: [['id' => 6], ['id' => 7]], totalRecordCount: 12, page: 3, recordsPerPage: 2);

    expect($paginator->getRecords())->toBe([['id' => 6], ['id' => 7]])
        ->and($paginator->getTotalRecordCount())->toBe(12)
        ->and($paginator->getPage())->toBe(3)
        ->and($paginator->getRecordsPerPage())->toBe(2)
        ->and($paginator->getLastPage())->toBe(6)
        ->and($paginator->count())->toBe(2);
});

it('accepts a Traversable and preserves its keys', function (): void {
    $records = (static function (): Generator {
        yield 'a' => ['id' => 1];
        yield 'b' => ['id' => 2];
    })();

    $paginator = new Paginator($records, totalRecordCount: 2, page: 1, recordsPerPage: 10);

    expect($paginator->getRecords())->toBe(['a' => ['id' => 1], 'b' => ['id' => 2]])
        ->and(iterator_to_array($paginator))->toBe(['a' => ['id' => 1], 'b' => ['id' => 2]]);
});

it('computes 1-based record numbers for the current page', function (): void {
    $paginator = new Paginator(records: [['id' => 6], ['id' => 7], ['id' => 8]], totalRecordCount: 23, page: 2, recordsPerPage: 5);

    expect($paginator->getFirstRecordNumber())->toBe(6)
        ->and($paginator->getLastRecordNumber())->toBe(8);
});

it('reports null record numbers on an empty page', function (): void {
    $paginator = new Paginator(records: [], totalRecordCount: 10, page: 9, recordsPerPage: 5);

    expect($paginator->getFirstRecordNumber())->toBeNull()
        ->and($paginator->getLastRecordNumber())->toBeNull();
});

it('answers the page-position queries', function (): void {
    $first = new Paginator(records: [['id' => 1]], totalRecordCount: 6, page: 1, recordsPerPage: 3);
    $last = new Paginator(records: [['id' => 6]], totalRecordCount: 6, page: 2, recordsPerPage: 3);
    $single = new Paginator(records: [['id' => 1]], totalRecordCount: 1, page: 1, recordsPerPage: 3);

    expect($first->onFirstPage())->toBeTrue()
        ->and($first->hasMorePages())->toBeTrue()
        ->and($first->hasPages())->toBeTrue()
        ->and($last->onFirstPage())->toBeFalse()
        ->and($last->hasMorePages())->toBeFalse()
        ->and($single->hasPages())->toBeFalse();
});

it('treats the all option as a single page', function (): void {
    $paginator = new Paginator(records: array_fill(0, 12, ['x' => 1]), totalRecordCount: 12, page: 1, recordsPerPage: 'all');

    expect($paginator->getRecordsPerPage())->toBe('all')
        ->and($paginator->getLastPage())->toBe(1)
        ->and($paginator->getFirstRecordNumber())->toBe(1)
        ->and($paginator->getLastRecordNumber())->toBe(12)
        ->and($paginator->hasPages())->toBeFalse();
});

it('renders every page in the window below eight pages', function (): void {
    $paginator = new Paginator(records: [['x' => 1]], totalRecordCount: 35, page: 4, recordsPerPage: 5);

    expect($paginator->getLastPage())->toBe(7)
        ->and($paginator->getPageWindow())->toBe([1, 2, 3, 4, 5, 6, 7]);
});

it('windows near the beginning as four pages then the last two', function (): void {
    $paginator = new Paginator(records: [['x' => 1]], totalRecordCount: 50, page: 3, recordsPerPage: 5);

    expect($paginator->getPageWindow())->toBe([1, 2, 3, 4, Paginator::GAP, 9, 10]);
});

it('windows near the end as the first two then the last four', function (): void {
    $paginator = new Paginator(records: [['x' => 1]], totalRecordCount: 50, page: 8, recordsPerPage: 5);

    expect($paginator->getPageWindow())->toBe([1, 2, Paginator::GAP, 7, 8, 9, 10]);
});

it('windows the middle as caps around the current page', function (): void {
    $paginator = new Paginator(records: [['x' => 1]], totalRecordCount: 50, page: 5, recordsPerPage: 5);

    expect($paginator->getPageWindow())->toBe([1, 2, Paginator::GAP, 5, Paginator::GAP, 9, 10]);
});

it('tolerates an overflow page past the end', function (): void {
    $paginator = new Paginator(records: [], totalRecordCount: 50, page: 99, recordsPerPage: 5);

    expect($paginator->hasMorePages())->toBeFalse()
        ->and($paginator->getPageWindow())->toBe([1, 2, Paginator::GAP, 7, 8, 9, 10]);
});

it('clones with replaced records through withRecords()', function (): void {
    $paginator = new Paginator(records: [['id' => 1]], totalRecordCount: 1, page: 1, recordsPerPage: 5);
    $keyed = $paginator->withRecords(['1' => ['id' => 1, '__key' => '1']]);

    expect($keyed)->not->toBe($paginator)
        ->and($keyed->getRecords())->toBe(['1' => ['id' => 1, '__key' => '1']])
        ->and($keyed->getTotalRecordCount())->toBe(1)
        ->and($paginator->getRecords())->toBe([['id' => 1]]);
});

it('refuses invalid construction values', function (int $total, int $page, int|string $perPage): void {
    expect(fn (): Paginator => new Paginator([], $total, $page, $perPage))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'negative total' => [-1, 1, 5],
    'zero page' => [0, 0, 5],
    'zero per page' => [0, 1, 0],
    'unknown string option' => [0, 1, 'some'],
]);
