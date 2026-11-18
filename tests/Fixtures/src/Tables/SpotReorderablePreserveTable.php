<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\ShelvedPreserve;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Preserves reordered by the position their embeddable holds.
 */
#[AsLiveComponent(name: 'Tables:SpotReorderablePreserveTable', template: '@Fixtures/tables/table.html.twig')]
class SpotReorderablePreserveTable implements InteractsWithTable
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
            ->query($this->entityManager->createQueryBuilder()->select('preserve')->from(ShelvedPreserve::class, 'preserve'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->reorderable('spot.position');
    }
}
