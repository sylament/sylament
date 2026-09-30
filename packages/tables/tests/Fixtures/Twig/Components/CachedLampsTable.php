<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Lamp;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shelf;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Stand;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The by-key writes over cached lamps: every lamp, deleted by key, under
 * `'all'`; the lamps of the first shelf or of the north stand, dissociated by
 * key, under `'shelf'` or `'stand'`.
 */
#[AsLiveComponent(name: 'CachedLampsTable', template: '@TablesFixtures/components/CachedLampsTable.html.twig')]
class CachedLampsTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public string $scope = 'all';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        $table
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->paginated([10]);

        if ($this->scope === 'all') {
            return $table
                ->query($this->entityManager->createQueryBuilder()->select('lamp')->from(Lamp::class, 'lamp'))
                ->toolbarActions([DeleteBulkAction::make()->fetchSelectedRecords(false)]);
        }

        return $table
            ->relationship(function (): ?Relationship {
                $owner = $this->scope === 'stand'
                    ? $this->entityManager->find(Stand::class, ['region' => 'north', 'code' => 'one'])
                    : $this->entityManager->find(Shelf::class, 1);

                return $owner === null ? null : new Relationship($owner, 'lamps');
            })
            ->toolbarActions([DissociateBulkAction::make()->fetchSelectedRecords(false)]);
    }
}
