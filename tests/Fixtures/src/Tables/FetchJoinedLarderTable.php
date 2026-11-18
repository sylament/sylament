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
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Larders whose query fetch-joins their jars, so each larder repeats once a jar in the
 * rows the query returns, selected without tracking deselections; its `archive` bulk
 * action records the keys it reads, sorted, in the {@see HookLog}.
 */
#[AsLiveComponent(name: 'Tables:FetchJoinedLarderTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class FetchJoinedLarderTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('larder')->from(Larder::class, 'larder')->leftJoin('larder.jars', 'jar')->addSelect('jar'))
            ->columns([
                TextColumn::make('name'),
            ])
            ->defaultSort('name')
            ->trackDeselectedRecords(false)
            ->toolbarActions([
                BulkAction::make('archive')->fetchSelectedRecords(false)->action(function (array $records): void {
                    $keys = array_map(static fn (mixed $key): string => is_string($key) ? $key : get_debug_type($key), $records);
                    sort($keys, SORT_NATURAL);

                    $this->hookLog->record('archive read ' . implode(', ', $keys));
                }),
            ]);
    }
}
