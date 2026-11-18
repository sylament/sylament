<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Range;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\RecordActionsPosition;
use Sylament\Tables\Enums\RecordCheckboxPosition;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of larder jars two to a page, sorted by position and groupable by seal, whose band
 * sums the positions (`No sum` over nothing), counts the days sealed on and ranges them, lists
 * the seals and counts the larders' names. Each variant configures one thing: which columns
 * summarize (`positionOnly`, or `unsummarized` for none), the columns (`positionHidden` hides
 * the summed one, `responsive` hides the labels from `lg` and shows the positions from `md`,
 * `columnSearch` searches the labels one by one), the summarizers (`scoped` adds a sum over the
 * positions past 1, `ownCount` a count over the days that scopes nothing, `iconCount` counts
 * the days with icons, `sealedOnHidden` hides both of the days' summarizers, `predicateHidden`
 * counts the positions ahead of their sum, hidden over a set of more than two jars and
 * recording each time it asks in the {@see HookLog}), the band (`noPageRow`, `noAllRow`), the
 * lane (`records` lists two array rows instead of querying), and the rest of the table
 * (`unpaginated`, every jar on one page; `reorderable` by position; `actions`, a record action
 * at `actionsPosition` and a bulk action with its checkbox at `checkboxPosition`).
 */
#[AsLiveComponent(name: 'Tables:SummarizedJarTable', template: '@Fixtures/tables/table.html.twig')]
class SummarizedJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    #[LiveProp]
    public string $actionsPosition = 'AfterColumns';

    #[LiveProp]
    public string $checkboxPosition = 'BeforeCells';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        $positionSummarizers = [Sum::make()->placeholder('No sum')];

        if ($this->has('predicateHidden')) {
            array_unshift($positionSummarizers, Count::make()->label('Counted')->hidden(function (QueryBuilder $query): bool {
                $this->hookLog->record('hidden?');

                return (int) (clone $query)->select('COUNT(jar.id)')->resetDQLPart('orderBy')->setFirstResult(0)->setMaxResults(null)->getQuery()->getSingleScalarResult() > 2;
            }));
        }

        if ($this->has('scoped')) {
            $positionSummarizers[] = Sum::make()->label('Past 1')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 1'));
        }

        $sealedOnSummarizers = [
            $this->has('iconCount') ? Count::make()->icons() : Count::make()->hidden($this->has('sealedOnHidden')),
            Range::make()->hidden($this->has('sealedOnHidden')),
        ];

        if ($this->has('ownCount')) {
            $sealedOnSummarizers[] = Count::make()->label('Own count')->query(static fn (QueryBuilder $query): QueryBuilder => $query);
        }

        $others = !$this->has('positionOnly') && !$this->has('unsummarized');

        $table
            ->columns([
                TextColumn::make('label')
                    ->searchable(isIndividual: $this->has('columnSearch'))
                    ->hiddenFrom($this->has('responsive') ? 'lg' : null),
                TextColumn::make('position')
                    ->toggleable()
                    ->hidden($this->has('positionHidden'))
                    ->visibleFrom($this->has('responsive') ? 'md' : null)
                    ->summarize($this->has('unsummarized') ? [] : $positionSummarizers),
                TextColumn::make('sealedOn')->dateTime('Y-m-d H:i')->summarize($others ? $sealedOnSummarizers : []),
                TextColumn::make('seal')->summarize($others ? Values::make() : []),
                TextColumn::make('larder.name')->summarize($others ? Count::make() : []),
            ])
            ->groups(['seal'])
            ->defaultSort('position')
            ->paginated($this->has('unpaginated') ? false : [2])
            ->summaries(pageCondition: !$this->has('noPageRow'), allTableCondition: !$this->has('noAllRow'));

        if ($this->has('records')) {
            $table->records(static fn (): array => [
                ['id' => 1, 'label' => 'Apricot', 'position' => 1],
                ['id' => 2, 'label' => 'Bramble', 'position' => 2],
            ]);
        } else {
            $table->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'));
        }

        if ($this->has('reorderable')) {
            $table->reorderable('position');
        }

        if ($this->has('actions')) {
            $table
                ->recordActions([Action::make('relabel')], position: array_find(RecordActionsPosition::cases(), fn (RecordActionsPosition $position): bool => $position->name === $this->actionsPosition))
                ->toolbarActions([BulkAction::make('archive')])
                ->recordCheckboxPosition(array_find(RecordCheckboxPosition::cases(), fn (RecordCheckboxPosition $position): bool => $position->name === $this->checkboxPosition));
        }

        return $table;
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
