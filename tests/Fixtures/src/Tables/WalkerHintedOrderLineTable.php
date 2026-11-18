<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\SqlOutputWalker;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Order lines, unpaginated, through a query that names its own output walker.
 */
#[AsLiveComponent(name: 'Tables:WalkerHintedOrderLineTable', template: '@Fixtures/tables/table.html.twig')]
class WalkerHintedOrderLineTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('line')->from(OrderLine::class, 'line')->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlOutputWalker::class))
            ->columns([
                TextColumn::make('note'),
            ])
            ->paginated(false);
    }
}
