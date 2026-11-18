<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder table whose cells read along the larder's associations: its supplier's name, its
 * jars' labels (repeated labels kept or, with `distinct`, dropped), its bins' notes, and two
 * names the larder cannot answer.
 */
#[AsLiveComponent(name: 'Tables:LarderStateTable', template: '@Fixtures/tables/table.html.twig')]
class LarderStateTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public bool $distinct = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('larder')->from(Larder::class, 'larder'))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('supplier.name')->default('No supplier'),
                TextColumn::make('jars.label')->listWithLineBreaks()->distinctList($this->distinct),
                TextColumn::make('bins.notes.text')->extraCellAttributes(fn (mixed $state): array => ['data-shape' => get_debug_type($state)]),
                TextColumn::make('nonexistent'),
                TextColumn::make('jars.nonexistent'),
            ])
            ->defaultSort('name');
    }
}
