<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Seedling owners keyed by several fields, a {@see Terrace} or the {@see Allotment} that
 * `ownerClass` names, whose query fetch-joins their seedlings, so each owner repeats once a
 * seedling in the rows the query returns, selected without tracking deselections; its
 * `archive` bulk action records how many keys it reads, and how many distinct, in the
 * {@see HookLog}.
 */
#[AsLiveComponent(name: 'Tables:FetchJoinedSeedlingOwnerTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class FetchJoinedSeedlingOwnerTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    /**
     * @var class-string<Terrace|Allotment>
     */
    #[LiveProp]
    public string $ownerClass = Terrace::class;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('owner')->from($this->ownerClass, 'owner')->leftJoin('owner.seedlings', 'seedling')->addSelect('seedling'))
            ->columns([
                TextColumn::make('number'),
            ])
            ->defaultSort('number')
            ->trackDeselectedRecords(false)
            ->toolbarActions([
                BulkAction::make('archive')->fetchSelectedRecords(false)->action(function (array $records): void {
                    $this->hookLog->record(sprintf('archive read %d keys, %d distinct', count($records), count(array_unique($records, SORT_REGULAR))));
                }),
            ]);
    }
}
