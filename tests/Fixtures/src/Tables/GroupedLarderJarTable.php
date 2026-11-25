<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\PaginationMode;
use Sylament\Tables\Enums\RecordActionsPosition;
use Sylament\Tables\Enums\RecordCheckboxPosition;
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A paginated table of larder jars with their positions summed, offering two
 * collapsible groupings by label, one titled by markup, with a record action, a bulk
 * action and an individually searchable label. Whether the record actions and the
 * checkbox sit before or after the cells, a default group, `groupsOnly()` and simple
 * pagination are mount props.
 */
#[AsLiveComponent(name: 'Tables:GroupedLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class GroupedLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public string $actionsPosition = 'BeforeCells';

    #[LiveProp]
    public string $checkboxPosition = 'BeforeCells';

    #[LiveProp]
    public ?string $defaultGroup = null;

    #[LiveProp]
    public bool $groupsOnly = false;

    #[LiveProp]
    public bool $simplePagination = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label')->searchable(isIndividual: true),
                TextColumn::make('position')->summarize(Sum::make()),
            ])
            ->defaultSort('position')
            ->paginated([5, 25])
            ->paginationMode($this->simplePagination ? PaginationMode::Simple : null)
            ->groups([
                Group::make('label')->collapsible(),
                Group::make('marked')
                    ->column('label')
                    ->collapsible()
                    ->getTitleFromRecordUsing(static fn (LarderJar $record): Markup => new Markup("<b>{$record->label}</b>", 'UTF-8')),
            ])
            ->defaultGroup($this->defaultGroup)
            ->groupsOnly($this->groupsOnly)
            ->recordActions([Action::make('relabel')], position: $this->actionsPosition === 'AfterCells' ? RecordActionsPosition::AfterCells : RecordActionsPosition::BeforeCells)
            ->toolbarActions([BulkAction::make('archive')])
            ->recordCheckboxPosition($this->checkboxPosition === 'AfterCells' ? RecordCheckboxPosition::AfterCells : RecordCheckboxPosition::BeforeCells);
    }
}
