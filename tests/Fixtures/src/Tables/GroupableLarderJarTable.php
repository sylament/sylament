<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Query\SortDirections;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of larder jars a user groups by their seal (collapsible), the day they were sealed
 * on, their supplier's name (its heading unprefixed) or their label, sorted by label by
 * default. Each variant configures one thing: the groups (`closureGroups` adds `initial`,
 * keyed by a label's first letter, `titled`, keyed so and titled per record, and two ordered
 * by position, `reordered` through a builder its closure returns and `orderedInPlace` through
 * the one it is handed, each closure recording what it is handed in the {@see HookLog};
 * `described` describes the supplier's group), the default group (`defaultSeal`, one the user
 * can select; `defaultPosition`, one by position the user cannot; `defaultInstance`, a
 * declared one by position labelled `Shelf position`), the settings (`settingsHidden`,
 * `directionHidden`, `dropdownOnDesktop`, `collapsed`, `relabelledTrigger`), the mode
 * (`groupsOnly`, `summarized` counting the labels), and the rest of the table (`paged`, two
 * rows a page; `striped`; `reorderable` by position; `selectable`, with a record action and
 * a bulk action).
 */
#[AsLiveComponent(name: 'Tables:GroupableLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class GroupableLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        $groups = [
            Group::make('seal')->collapsible(),
            Group::make('sealedOn')->date(),
            Group::make('supplier.name')
                ->titlePrefixedWithLabel(false)
                ->getDescriptionFromRecordUsing($this->has('described') ? static fn (string $title, LarderJar $record, string $column): string => "{$title} by {$column}, first {$record->label}" : null),
            'label',
        ];

        if ($this->has('closureGroups')) {
            $initial = function (LarderJar $record, string $column): string {
                $this->hookLog->record("key {$column}");

                return mb_substr($record->label, 0, 1);
            };

            $groups[] = Group::make('initial')->column('label')->getKeyFromRecordUsing($initial);
            $groups[] = Group::make('titled')
                ->column('label')
                ->getKeyFromRecordUsing($initial)
                ->getTitleFromRecordUsing(function (LarderJar $record): string {
                    $this->hookLog->record('title');

                    return "{$record->label} jar";
                });
            $groups[] = Group::make('reordered')
                ->column('position')
                ->orderQueryUsing(function (QueryBuilder $query, string $direction, string $column): QueryBuilder {
                    $this->hookLog->record("order {$column} {$direction}");

                    return (clone $query)->addOrderBy("jar.{$column}", SortDirections::from($direction === 'asc' ? 'desc' : 'asc'));
                });
            $groups[] = Group::make('orderedInPlace')
                ->column('position')
                ->orderQueryUsing(function (QueryBuilder $query, string $direction, string $column): void {
                    $this->hookLog->record("order {$column} {$direction}");

                    $query->addOrderBy("jar.{$column}", SortDirections::from($direction === 'asc' ? 'desc' : 'asc'));
                });
        }

        $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label')->sortable()->summarize($this->has('summarized') ? Count::make() : []),
                TextColumn::make('seal'),
                TextColumn::make('sealedOn'),
                TextColumn::make('supplier.name'),
                TextColumn::make('position'),
            ])
            ->defaultSort('label')
            ->groups($groups)
            ->groupsOnly($this->has('groupsOnly'))
            ->groupingSettingsHidden($this->has('settingsHidden'))
            ->groupingDirectionSettingHidden($this->has('directionHidden'))
            ->groupingSettingsInDropdownOnDesktop($this->has('dropdownOnDesktop'))
            ->collapsedGroupsByDefault($this->has('collapsed'))
            ->striped($this->has('striped'));

        match (true) {
            $this->has('defaultSeal') => $table->defaultGroup('seal'),
            $this->has('defaultPosition') => $table->defaultGroup('position'),
            $this->has('defaultInstance') => $table->defaultGroup(Group::make('position')->label('Shelf position')),
            default => null,
        };

        if ($this->has('relabelledTrigger')) {
            $table->groupRecordsTriggerAction(static fn (Action $action): Action => $action->label('Regroup'));
        }

        if ($this->has('paged')) {
            $table->paginated([2]);
        }

        if ($this->has('reorderable')) {
            $table->reorderable('position');
        }

        if ($this->has('selectable')) {
            $table
                ->recordActions([Action::make('relabel')])
                ->toolbarActions([BulkAction::make('archive')]);
        }

        return $table;
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
