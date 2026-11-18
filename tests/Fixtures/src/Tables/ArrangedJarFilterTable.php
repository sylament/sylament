<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\Toggle;
use Sylament\Forms\Components\ToggleButtons;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\FiltersLayout;
use Sylament\Tables\Enums\FiltersResetActionPosition;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Filters\Indicator;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars searchable by label, globally and in its own column, whose `stock` bulk
 * action records the labels it reads in the {@see HookLog}, filtered by whether they are
 * labelled, by their seal, by whether they carry a seal (its chip not removable) and, pushed
 * after those, by boolean fields that filter nothing (a boolean select, radio and toggle
 * buttons, a checkbox, a toggle, and a hidden field holding `true` that its reset state
 * clears to `null`). Its filters render in the layout named by `layout`, a `FiltersLayout`
 * case.
 * Each variant configures one thing: `live` filters on each field write, `resetInFooter`
 * moves the reset beside the apply button, `sized` makes the filters panel `30rem` wide and
 * at most `20rem` high, `twoColumns` lays the filters form out in two columns, `customized` relabels
 * the apply and remove-all buttons and gives the trigger and remove-all attributes of their own,
 * `indicatorsHidden` hides the chips, `keepsSelection` keeps the selection through a filter
 * change, `paged` shows two jars a page, and `refiltered` declares a lone `sealed` filter in
 * place of every other.
 */
#[AsLiveComponent(name: 'Tables:ArrangedJarFilterTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ArrangedJarFilterTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public string $layout = 'Dropdown';

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

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label')->searchable(isIndividual: true),
            ])
            ->filters([
                Filter::make('labelled')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.label <> ''")),
                SelectFilter::make('seal')->options(['wax' => 'Wax', 'screw' => 'Screw']),
                Filter::make('pinned')
                    ->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.seal IS NOT NULL'))
                    ->indicateUsing(static fn (array $state): array => ($state['isActive'] ?? false) ? [Indicator::make('Pinned')->removable(false)] : []),
            ], layout: array_find(FiltersLayout::cases(), fn (FiltersLayout $layout): bool => $layout->name === $this->layout)
                ?? throw new LogicException(sprintf('No filters layout is named [%s].', $this->layout)))
            ->pushFilters([
                Filter::make('flags')->schema([
                    Select::make('chosen')->boolean(),
                    Radio::make('picked')->boolean(),
                    ToggleButtons::make('toggled')->boolean(),
                    Checkbox::make('ticked'),
                    Toggle::make('switched'),
                    Hidden::make('kept')->default(true),
                ])->resetState(['kept' => null]),
            ])
            ->toolbarActions([
                BulkAction::make('stock')->action($this->stock(...)),
            ])
            ->deferFilters(!$this->has('live'))
            ->deselectAllRecordsWhenFiltered(!$this->has('keepsSelection'))
            ->hiddenFilterIndicators($this->has('indicatorsHidden'));

        if ($this->has('resetInFooter')) {
            $table->filtersResetActionPosition(FiltersResetActionPosition::Footer);
        }

        if ($this->has('sized')) {
            $table->filtersFormWidth('30rem')->filtersFormMaxHeight('20rem');
        }

        if ($this->has('twoColumns')) {
            $table->filtersFormColumns(2);
        }

        if ($this->has('customized')) {
            $table
                ->filtersTriggerAction(static fn (Action $action): Action => $action->extraAttributes(['data-customized' => 'trigger']))
                ->filtersApplyAction(static fn (Action $action): Action => $action->label('Filter jars'))
                ->filtersRemoveAllAction(static fn (Action $action): Action => $action->label('Clear jar filters')->extraAttributes(['data-customized' => 'remove-all']));
        }

        if ($this->has('paged')) {
            $table->paginated([2]);
        }

        if ($this->has('refiltered')) {
            $table->filters([Filter::make('sealed')]);
        }

        return $table;
    }

    /**
     * @param array<mixed> $records
     */
    private function stock(array $records): void
    {
        $this->hookLog->record('stocked ' . implode(', ', array_map(static fn (mixed $jar): string => $jar instanceof LarderJar ? $jar->label : get_debug_type($jar), $records)));
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
