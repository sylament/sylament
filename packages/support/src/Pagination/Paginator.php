<?php

declare(strict_types=1);

namespace Sylament\Support\Pagination;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * One page of table records plus the counts a pagination UI needs. A
 * `records()` closure that received `page` / `recordsPerPage` slices its data
 * itself and returns this; the carrier never slices anything.
 *
 * @implements IteratorAggregate<array-key, object|array<string, mixed>>
 */
final readonly class Paginator implements Countable, IteratorAggregate
{
    public const string GAP = '…';

    /**
     * @var array<array-key, object|array<string, mixed>>
     */
    private array $records;

    /**
     * @var int|'all'
     */
    private int|string $recordsPerPage;

    /**
     * `$recordsPerPage` is a positive integer or the string `all`; anything
     * else is rejected here. A null `$totalRecordCount` means the total is
     * unknown (simple pagination), and `$hasMoreRecords` is read only then.
     *
     * @param iterable<array-key, object|array<string, mixed>> $records
     */
    public function __construct(
        iterable $records,
        private ?int $totalRecordCount,
        private int $page,
        int|string $recordsPerPage,
        private bool $hasMoreRecords = false,
    ) {
        if ($totalRecordCount !== null && $totalRecordCount < 0) {
            throw new InvalidArgumentException(sprintf('The total record count must be zero or more, got %d.', $totalRecordCount));
        }

        if ($page < 1) {
            throw new InvalidArgumentException(sprintf('The page must be 1 or more, got %d.', $page));
        }

        if (is_int($recordsPerPage)) {
            if ($recordsPerPage < 1) {
                throw new InvalidArgumentException(sprintf('The records per page must be a positive integer or [all], got %d.', $recordsPerPage));
            }
        } elseif ($recordsPerPage !== 'all') {
            throw new InvalidArgumentException(sprintf('The records per page must be a positive integer or [all], got [%s].', $recordsPerPage));
        }

        $this->records = is_array($records) ? $records : iterator_to_array($records);
        $this->recordsPerPage = $recordsPerPage;
    }

    /**
     * @return array<array-key, object|array<string, mixed>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function getTotalRecordCount(): ?int
    {
        return $this->totalRecordCount;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return int|'all'
     */
    public function getRecordsPerPage(): int|string
    {
        return $this->recordsPerPage;
    }

    public function getLastPage(): ?int
    {
        if ($this->totalRecordCount === null) {
            return null;
        }

        if ($this->recordsPerPage === 'all') {
            return 1;
        }

        return max(1, (int) ceil($this->totalRecordCount / $this->recordsPerPage));
    }

    public function getFirstRecordNumber(): ?int
    {
        if ($this->records === []) {
            return null;
        }

        if ($this->recordsPerPage === 'all') {
            return 1;
        }

        return ($this->page - 1) * $this->recordsPerPage + 1;
    }

    public function getLastRecordNumber(): ?int
    {
        $first = $this->getFirstRecordNumber();

        if ($first === null) {
            return null;
        }

        return $first + count($this->records) - 1;
    }

    public function onFirstPage(): bool
    {
        return $this->page === 1;
    }

    public function hasMorePages(): bool
    {
        $lastPage = $this->getLastPage();

        return $lastPage === null ? $this->hasMoreRecords : $this->page < $lastPage;
    }

    public function hasPages(): bool
    {
        $lastPage = $this->getLastPage();

        if ($lastPage === null) {
            return $this->page > 1 || $this->hasMoreRecords;
        }

        return $lastPage > 1;
    }

    /**
     * The page items a footer renders — Laravel's `UrlWindow` shape at
     * `onEachSide(0)`, with `self::GAP` marking each elided range.
     *
     * @return list<int|string>
     */
    public function getPageWindow(): array
    {
        $lastPage = $this->getLastPage() ?? throw new LogicException('A page window needs a total record count; this paginator was built without one (simple pagination).');

        if ($lastPage < 8) {
            return range(1, $lastPage);
        }

        if ($this->page <= 4) {
            return [...range(1, 4), self::GAP, $lastPage - 1, $lastPage];
        }

        if ($this->page > $lastPage - 4) {
            return [1, 2, self::GAP, ...range($lastPage - 3, $lastPage)];
        }

        return [1, 2, self::GAP, $this->page, self::GAP, $lastPage - 1, $lastPage];
    }

    /**
     * @param array<array-key, object|array<string, mixed>> $records
     */
    public function withRecords(array $records): self
    {
        return new self($records, $this->totalRecordCount, $this->page, $this->recordsPerPage, $this->hasMoreRecords);
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->records);
    }
}
