<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table over one entity whose toolbar deletes the selection by key, the shape each
 * by-key host shares; a host adds its own toolbar actions after the plain `delete`.
 */
abstract class ByKeyDeletingTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return class-string
     */
    abstract protected function recordClass(): string;

    abstract protected function column(): string;

    /**
     * @return list<Action>
     */
    protected function moreToolbarActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('record')->from($this->recordClass(), 'record'))
            ->columns([
                TextColumn::make($this->column()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->fetchSelectedRecords(false),
                ...$this->moreToolbarActions(),
            ]);
    }
}
