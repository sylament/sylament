<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Bazaar;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Depot;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The by-key arm of {@see DissociateBulkAction} over a collection whose
 * foreign key the target class's own bare names do not reach: the first
 * depot's `'toasters'` or `'dials'`, or the north bazaar's `'purchases'`.
 */
#[AsLiveComponent(name: 'DepotDissociateByKeyTable', template: '@TablesFixtures/components/DepotDissociateByKeyTable.html.twig')]
class DepotDissociateByKeyTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public string $association = 'toasters';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $owner = $this->association === 'purchases'
                    ? $this->entityManager->find(Bazaar::class, ['region' => 'north', 'code' => 'one'])
                    : $this->entityManager->find(Depot::class, 1);

                return $owner === null ? null : new Relationship($owner, $this->association);
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->toolbarActions([DissociateBulkAction::make()->fetchSelectedRecords(false)])
            ->paginated([10]);
    }
}
