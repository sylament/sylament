<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of larder jars a user reorders by their position, with rows linking to each
 * jar, a sortable label, a header and a record action, and a reorder trigger stamped
 * with the mode it opens; the hooks around a drag record the order they were handed.
 * The reorder column, its condition and its authorization are mount props.
 */
#[AsLiveComponent(name: 'Tables:ReorderableLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ReorderableLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public ?string $reorderColumn = 'position';

    #[LiveProp]
    public bool $reorderCondition = true;

    #[LiveProp]
    public bool $reorderAuthorized = true;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label')->sortable(),
            ])
            ->defaultSort('position')
            ->recordUrl(static fn (LarderJar $record): string => "/jars/{$record->id}")
            ->reorderable($this->reorderColumn, condition: $this->reorderCondition)
            ->authorizeReorder($this->reorderAuthorized)
            ->reorderRecordsTriggerAction(static fn (Action $action, bool $isReordering): Action => $action->extraAttributes(['data-opens' => $isReordering ? 'browsing' : 'reordering']))
            ->beforeReordering(function (array $order): void {
                $this->hookLog->record('before ' . implode(',', array_filter($order, is_string(...))));
            })
            ->afterReordering(function (array $order): void {
                $this->hookLog->record('after ' . implode(',', array_filter($order, is_string(...))));
            })
            ->headerActions([Action::make('restock')])
            ->recordActions([Action::make('rename')]);
    }
}
