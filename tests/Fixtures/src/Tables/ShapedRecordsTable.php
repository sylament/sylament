<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Generator;
use LogicException;
use Sylament\Support\Pagination\Paginator;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\Record\ShelfItem;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A paginated table whose `records()` closure returns the shape its `shape` names: array
 * rows (`arrays`), object rows keyed by an integer and by a uuid (`objects`), an object row
 * with no id (`idless`), a generator yielding one key twice (`generator`), the array rows on a page carrying
 * its total (`page`) or none (`simplePage`), a string (`text`), a row that is a number
 * (`numberRow`), an array row with an integer key (`listRow`), a blank `__key` (`blankKey`),
 * or array rows beside a declared product query (`besideQuery`).
 */
#[AsLiveComponent(name: 'Tables:ShapedRecordsTable', template: '@Fixtures/tables/table.html.twig')]
class ShapedRecordsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public const string UUID = '0198f6a4-6a3e-7c2b-9d4e-5f6a7b8c9d0e';

    #[LiveProp]
    public string $shape = 'arrays';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        if ($this->shape === 'besideQuery') {
            $table->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'));
        }

        return $table
            ->records(fn (): mixed => $this->records())
            ->columns([
                TextColumn::make('name'),
            ]);
    }

    private function records(): mixed
    {
        return match ($this->shape) {
            'arrays', 'besideQuery' => ['lamp' => ['__key' => 'lamp', 'name' => 'Lamp'], 'vase' => ['name' => 'Vase']],
            'objects' => [new ShelfItem('Lamp', 7), new ShelfItem('Vase', Uuid::fromString(self::UUID))],
            'idless' => [new ShelfItem('Lamp')],
            'generator' => $this->yieldTwice(),
            'page' => new Paginator(['lamp' => ['__key' => 'lamp', 'name' => 'Lamp'], 'vase' => ['name' => 'Vase']], totalRecordCount: 12, page: 1, recordsPerPage: 10),
            'simplePage' => new Paginator([['__key' => 'lamp', 'name' => 'Lamp']], totalRecordCount: null, page: 1, recordsPerPage: 10, hasMoreRecords: true),
            'text' => 'Lamp',
            'numberRow' => [7],
            'listRow' => [['__key' => 'lamp', 'Lamp']],
            'blankKey' => [['__key' => ' ', 'name' => 'Lamp']],
            default => throw new LogicException("No records shape is named [{$this->shape}]."),
        };
    }

    /**
     * @return Generator<string, array<string, string>>
     */
    private function yieldTwice(): Generator
    {
        yield 'first' => ['__key' => 'lamp', 'name' => 'Lamp'];
        yield 'second' => ['__key' => 'lamp', 'name' => 'Lantern'];
    }
}
