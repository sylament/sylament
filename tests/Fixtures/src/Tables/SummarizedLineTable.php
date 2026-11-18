<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of composite-keyed lines two to a page, sorted by line number, whose band sums the
 * line numbers; the line class (`OrderLine` by default, or `InvoiceLine`) is a mount prop.
 */
#[AsLiveComponent(name: 'Tables:SummarizedLineTable', template: '@Fixtures/tables/table.html.twig')]
class SummarizedLineTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var class-string
     */
    #[LiveProp]
    public string $lineClass = OrderLine::class;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('line')->from($this->lineClass, 'line'))
            ->columns([
                TextColumn::make('note'),
                TextColumn::make('line')->summarize(Sum::make()),
            ])
            ->defaultSort('line')
            ->paginated([2]);
    }
}
