<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\SqlOutputWalker;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\PaginationMode;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\GreenhouseVent;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\LanternStand;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table paged two records at a time (or all of them) over a root of each identifier shape
 * the mount names, fetch-joining the root's collection where the shape has one: `larder`
 * (a generated key, its jars), `suppliedLarder` (the same root, its to-one supplier),
 * `stand` (two scalars, its lanterns), `allotment` (an association and a scalar, its
 * seedlings), `ventSeedlings` (one association alone, its seedlings), and with no join
 * `orderLine` (two scalars), `invoiceLine` (an association and a scalar), `vent` (one
 * association alone) and `product` (a generated key); a vent has no default sort, so it
 * sorts by its identity alone.
 * With `walker` its query names its own output walker, and `mode` names a `PaginationMode`
 * case.
 */
#[AsLiveComponent(name: 'Tables:KeyShapedPagedTable', template: '@Fixtures/tables/table.html.twig')]
class KeyShapedPagedTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public string $root = 'larder';

    #[LiveProp]
    public bool $walker = false;

    #[LiveProp]
    public ?string $mode = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        [$class, $collection, $sort, $columns] = match ($this->root) {
            'suppliedLarder' => [Larder::class, 'supplier', 'name', ['name']],
            'stand' => [LanternStand::class, 'lanterns', 'aisle', ['aisle', 'lanterns.name']],
            'allotment' => [Allotment::class, 'seedlings', 'number', ['number', 'seedlings.name']],
            'orderLine' => [OrderLine::class, null, 'note', ['note']],
            'invoiceLine' => [InvoiceLine::class, null, 'note', ['note']],
            'vent' => [GreenhouseVent::class, null, null, []],
            'ventSeedlings' => [GreenhouseVent::class, 'seedlings', null, ['seedlings.name']],
            'product' => [Product::class, null, 'name', ['name']],
            default => [Larder::class, 'jars', 'name', ['name', 'jars.label']],
        };

        $query = $this->entityManager->createQueryBuilder()->select('root')->from($class, 'root');

        if ($collection !== null) {
            $query->addSelect('joined')->leftJoin("root.{$collection}", 'joined');
        }

        if ($this->walker) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlOutputWalker::class);
        }

        $columns = array_map(TextColumn::make(...), $columns);

        if ($class === GreenhouseVent::class) {
            array_unshift($columns, TextColumn::make('vent')->state(static fn (GreenhouseVent $record): string => "Vent {$record->greenhouse->id}"));
        }

        $table
            ->query($query)
            ->columns($columns)
            ->defaultSort($sort)
            ->paginated([2, 'all']);

        if ($this->mode === 'Simple') {
            $table->paginationMode(PaginationMode::Simple);
        }

        return $table;
    }
}
