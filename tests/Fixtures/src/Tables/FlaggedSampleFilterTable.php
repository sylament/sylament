<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\TernaryFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Column-kind samples filtered by whether they are flagged, a boolean column.
 */
#[AsLiveComponent(name: 'Tables:FlaggedSampleFilterTable', template: '@Fixtures/tables/table.html.twig')]
class FlaggedSampleFilterTable implements InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('sample')->from(ColumnKindSample::class, 'sample'))
            ->columns([
                TextColumn::make('body'),
            ])
            ->filters([
                TernaryFilter::make('flagged'),
            ]);
    }
}
