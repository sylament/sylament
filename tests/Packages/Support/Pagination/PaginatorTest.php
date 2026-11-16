<?php

declare(strict_types=1);

use Sylament\Support\Pagination\Paginator;

it('refuses a negative total, a page below one, and records per page that are neither a positive count nor all', function (?int $total, int $page, int|string $perPage, string $message): void {
    expect(fn (): Paginator => new Paginator([], $total, $page, $perPage))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'a negative total' => [-1, 1, 10, 'The total record count must be zero or more, got -1.'],
    'a page below one' => [30, 0, 10, 'The page must be 1 or more, got 0.'],
    'a count below one' => [30, 1, 0, 'The records per page must be a positive integer or [all], got 0.'],
    'a string other than all' => [30, 1, 'ten', 'The records per page must be a positive integer or [all], got [ten].'],
]);

it('takes the records of any iterable as its page', function (): void {
    $records = (function (): Generator {
        yield 'order-1' => ['id' => 1];
        yield 'order-2' => ['id' => 2];
    })();

    $paginator = new Paginator($records, 30, 1, 'all');

    expect($paginator->getRecords())->toBe(['order-1' => ['id' => 1], 'order-2' => ['id' => 2]])
        ->and($paginator)->toHaveCount(2)
        ->and(iterator_to_array($paginator))->toBe(['order-1' => ['id' => 1], 'order-2' => ['id' => 2]]);
});

it('counts its last page from the total, one at least, one for all records on a page, and none without a total', function (?int $total, int|string $perPage, ?int $lastPage): void {
    expect(new Paginator([], $total, 1, $perPage)->getLastPage())->toBe($lastPage);
})->with([
    'a part-filled last page' => [25, 10, 3],
    'an exactly filled last page' => [30, 10, 3],
    'no records' => [0, 10, 1],
    'all records on a page' => [30, 'all', 1],
    'no total' => [null, 10, null],
]);

it('numbers the first and the last record of its page, and neither on an empty page', function (): void {
    $page = new Paginator([['id' => 21], ['id' => 22], ['id' => 23]], 23, 3, 10);
    $all = new Paginator([['id' => 1], ['id' => 2]], 2, 1, 'all');
    $empty = new Paginator([], 23, 4, 10);

    expect([$page->getFirstRecordNumber(), $page->getLastRecordNumber()])->toBe([21, 23])
        ->and([$all->getFirstRecordNumber(), $all->getLastRecordNumber()])->toBe([1, 2])
        ->and([$empty->getFirstRecordNumber(), $empty->getLastRecordNumber()])->toBe([null, null]);
});

it('knows its first page, and whether more pages follow and there is more than one, from the total or else from whether more records follow', function (): void {
    $first = new Paginator([], 30, 1, 10);
    $last = new Paginator([], 30, 3, 10, hasMoreRecords: true);
    $only = new Paginator([], 5, 1, 10, hasMoreRecords: true);
    $simpleFirst = new Paginator([], null, 1, 10);
    $simpleMore = new Paginator([], null, 1, 10, hasMoreRecords: true);
    $simpleLater = new Paginator([], null, 2, 10);

    expect([$first->onFirstPage(), $first->hasMorePages(), $first->hasPages()])->toBe([true, true, true])
        ->and([$last->onFirstPage(), $last->hasMorePages(), $last->hasPages()])->toBe([false, false, true])
        ->and([$only->hasMorePages(), $only->hasPages()])->toBe([false, false])
        ->and([$simpleFirst->hasMorePages(), $simpleFirst->hasPages()])->toBe([false, false])
        ->and([$simpleMore->hasMorePages(), $simpleMore->hasPages()])->toBe([true, true])
        ->and([$simpleLater->onFirstPage(), $simpleLater->hasMorePages(), $simpleLater->hasPages()])->toBe([false, false, true]);
});

it('windows its pages, every page under eight and otherwise both ends with a gap for each elided range', function (int $total, int $page, array $window): void {
    expect(new Paginator([], $total, $page, 1)->getPageWindow())->toBe($window);
})->with([
    'fewer than eight pages' => [7, 4, [1, 2, 3, 4, 5, 6, 7]],
    'near the start' => [15, 4, [1, 2, 3, 4, Paginator::GAP, 14, 15]],
    'near the end' => [15, 12, [1, 2, Paginator::GAP, 12, 13, 14, 15]],
    'in the middle' => [15, 8, [1, 2, Paginator::GAP, 8, Paginator::GAP, 14, 15]],
]);

it('refuses a page window without a total', function (): void {
    expect(fn (): array => new Paginator([], null, 1, 10, hasMoreRecords: true)->getPageWindow())
        ->toThrow(LogicException::class, 'A page window needs a total record count');
});

it('hands back a new carrier holding other records with the same counts', function (): void {
    $paginator = new Paginator([['id' => 3], ['id' => 4]], 30, 2, 2, hasMoreRecords: true);

    $rekeyed = $paginator->withRecords(['order-3' => ['id' => 3], 'order-4' => ['id' => 4]]);

    expect($rekeyed)->not->toBe($paginator)
        ->and($rekeyed->getRecords())->toBe(['order-3' => ['id' => 3], 'order-4' => ['id' => 4]])
        ->and([$rekeyed->getTotalRecordCount(), $rekeyed->getPage(), $rekeyed->getRecordsPerPage(), $rekeyed->getFirstRecordNumber()])->toBe([30, 2, 2, 3])
        ->and($paginator->getRecords())->toBe([['id' => 3], ['id' => 4]]);
});
