<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\TrellisVine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A vine table whose cell reads its trellis's vines through the trellis's array-answering
 * getter, naming each vine it is handed and any other item by its type.
 */
#[AsLiveComponent(name: 'Tables:TrellisVineTable', template: '@Fixtures/tables/table.html.twig')]
class TrellisVineTable implements InteractsWithTable
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
            ->query($this->entityManager->createQueryBuilder()->select('vine')->from(TrellisVine::class, 'vine'))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('trellis.vines')
                    ->listWithLineBreaks()
                    ->formatStateUsing(static fn (mixed $state): string => $state instanceof TrellisVine ? $state->name : get_debug_type($state)),
            ])
            ->defaultSort('name');
    }
}
