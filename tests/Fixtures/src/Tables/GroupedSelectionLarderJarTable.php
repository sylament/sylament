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
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Larder jars grouped by their supplier's name, the jars with no supplier forming a group
 * of their own, behind an `archive` bulk action that turns the checkboxes on; with
 * `pageOnly` a group's checkbox selects only the jars of the page.
 */
#[AsLiveComponent(name: 'Tables:GroupedSelectionLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class GroupedSelectionLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public bool $pageOnly = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->defaultSort('label')
            ->groups([Group::make('supplier.name')])
            ->defaultGroup('supplier.name')
            ->selectCurrentPageOnly($this->pageOnly)
            ->toolbarActions([BulkAction::make('archive')]);
    }
}
