<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\Summarizers\Summarizer;
use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of larders one to a page, sorted and groupable by name, whose band counts their
 * numbers, hands the count of their jars and the labels of those jars to `using()` closures
 * that answer with the column they are handed, and so reads nothing a query can aggregate
 * from either. Each variant configures one thing: `aggregateSummed` sums the jar count
 * instead, `handOnly` drops the count of numbers so nothing batches, and `joined` fetch-joins
 * each larder's jars into the table's query.
 */
#[AsLiveComponent(name: 'Tables:SummarizedLarderTable', template: '@Fixtures/tables/table.html.twig')]
class SummarizedLarderTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        $query = $this->entityManager->createQueryBuilder()->select('larder')->from(Larder::class, 'larder');

        if ($this->has('joined')) {
            $query->addSelect('jar')->leftJoin('larder.jars', 'jar');
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('id')->label('Number')->summarize($this->has('handOnly') ? [] : Count::make()),
                TextColumn::make('jars_count')->counts('jars')->summarize($this->has('aggregateSummed')
                    ? Sum::make()
                    : Summarizer::make()->label('By hand')->using(static fn (string $attribute): string => "{$attribute} counted")),
                TextColumn::make('jars.label')->summarize(Values::make()->using(static fn (string $attribute): array => [$attribute])),
            ])
            ->groups(['name'])
            ->defaultSort('name')
            ->paginated([1]);
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
