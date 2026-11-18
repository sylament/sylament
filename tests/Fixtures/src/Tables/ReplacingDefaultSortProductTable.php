<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\CodeKeyedSlate;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table, sortable and searchable by name, whose default sort returns a builder of
 * its own in place of the query: the slates whose note is not `hidden`, keyed by their code.
 */
#[AsLiveComponent(name: 'Tables:ReplacingDefaultSortProductTable', template: '@Fixtures/tables/table.html.twig')]
class ReplacingDefaultSortProductTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable {
        getTableRecordKey as getQueriedTableRecordKey;
    }

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('note'),
            ])
            ->defaultSort(fn (): QueryBuilder => $this->entityManager->createQueryBuilder()
                ->select('slate')
                ->from(CodeKeyedSlate::class, 'slate')
                ->where("slate.note <> 'hidden'"));
    }

    /**
     * The table's identity reads follow its declared query, so the slates the replacement
     * lists are keyed here, by their code.
     *
     * @param object|array<string, mixed> $record
     */
    public function getTableRecordKey(object|array $record): string
    {
        return $record instanceof CodeKeyedSlate ? $record->code : $this->getQueriedTableRecordKey($record);
    }
}
