<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\PaginationMode;
use Sylament\Tables\Enums\RecordCheckboxPosition;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table, sortable and searchable by name, globally and on its own, with a `lit`
 * filter keeping the products whose name starts with an L, whose selection settings are
 * its mount props: `selectable` (the switch, unset by default), `bulk` (its bulk actions
 * `visible`, `hidden` or `none`), `refuses` (a selectability callback refusing the Vase,
 * recording each record it is handed by name and by class), `checkboxPosition` (a
 * {@see RecordCheckboxPosition} case name), `pageOnly`, `tracks`, `groupsOnly`, `grouped`
 * (the bulk actions in one group), `deprecated` (declared through `bulkActions()` and
 * `pushBulkActions()`), `defersLoading`, `keepsSelectionWhenFiltered`, `simplePagination`
 * and `records` (six array rows instead of the products). Its bulk actions record what they
 * read in the {@see HookLog}, sorted: `archive` the selected records, `archiveByKey` their keys and
 * `recount` how many it reads before and after clearing the selection itself.
 */
#[AsLiveComponent(name: 'Tables:ConfiguredSelectionProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ConfiguredSelectionProductTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    public const array ROWS = [
        ['__key' => 'chair', 'name' => 'Chair'],
        ['__key' => 'desk', 'name' => 'Desk'],
        ['__key' => 'lamp', 'name' => 'Lamp'],
        ['__key' => 'rug', 'name' => 'Rug'],
        ['__key' => 'sofa', 'name' => 'Sofa'],
        ['__key' => 'vase', 'name' => 'Vase'],
    ];

    #[LiveProp]
    public ?bool $selectable = null;

    #[LiveProp]
    public string $bulk = 'visible';

    #[LiveProp]
    public bool $refuses = false;

    #[LiveProp]
    public ?string $checkboxPosition = null;

    #[LiveProp]
    public bool $pageOnly = false;

    #[LiveProp]
    public bool $tracks = true;

    #[LiveProp]
    public bool $groupsOnly = false;

    #[LiveProp]
    public bool $grouped = false;

    #[LiveProp]
    public bool $deprecated = false;

    #[LiveProp]
    public bool $defersLoading = false;

    #[LiveProp]
    public bool $keepsSelectionWhenFiltered = false;

    #[LiveProp]
    public bool $simplePagination = false;

    #[LiveProp]
    public bool $records = false;

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
        $table = $this->records
            ? $table->records(static fn (): array => self::ROWS)
            : $table
                ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
                ->filters([
                    Filter::make('lit')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("product.name LIKE 'L%'")),
                ])
                ->defaultSort('name');

        $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(isIndividual: true),
            ])
            ->selectable($this->selectable)
            ->recordCheckboxPosition(array_find(RecordCheckboxPosition::cases(), fn (RecordCheckboxPosition $case): bool => $case->name === $this->checkboxPosition))
            ->selectCurrentPageOnly($this->pageOnly)
            ->trackDeselectedRecords($this->tracks)
            ->selectGroupsOnly($this->groupsOnly)
            ->deferLoading($this->defersLoading)
            ->deselectAllRecordsWhenFiltered(!$this->keepsSelectionWhenFiltered)
            ->paginationMode($this->simplePagination ? PaginationMode::Simple : null);

        if ($this->refuses) {
            $table->checkIfRecordIsSelectableUsing(function (mixed $record, Product $product): bool {
                $this->hookLog->record(sprintf('asked about %s, the same %s', $product->name, $record === $product ? 'record' : 'name only'));

                return $product->name !== 'Vase';
            });
        }

        $actions = match ($this->bulk) {
            'visible' => $this->bulkActions(),
            'hidden' => array_map(static fn (BulkAction $action): BulkAction => $action->hidden(), $this->bulkActions()),
            default => [],
        };

        if ($this->grouped) {
            return $table->groupedBulkActions($actions);
        }

        if ($this->deprecated) {
            return $table->bulkActions(array_slice($actions, 0, 1))->pushBulkActions(array_slice($actions, 1));
        }

        return $table->toolbarActions($actions);
    }

    /**
     * @return list<BulkAction>
     */
    private function bulkActions(): array
    {
        return [
            BulkAction::make('archive')->action(function (array $records): void {
                $this->hookLog->record('archive read ' . $this->sorted(array_map($this->nameOf(...), $records)));
            }),
            BulkAction::make('archiveByKey')->fetchSelectedRecords(false)->action(function (array $records): void {
                $this->hookLog->record('archiveByKey read ' . $this->sorted(array_map(static fn (mixed $key): string => is_string($key) ? $key : get_debug_type($key), $records)));
            }),
            BulkAction::make('recount')->action(function (): void {
                $before = count($this->getSelectedTableRecords());
                $this->deselectAllTableRecords();

                $this->hookLog->record(sprintf('recount read %d, then %d', $before, count($this->getSelectedTableRecords())));
            }),
        ];
    }

    /**
     * @param array<string> $values
     */
    private function sorted(array $values): string
    {
        sort($values, SORT_NATURAL);

        return implode(', ', $values);
    }

    private function nameOf(mixed $record): string
    {
        return match (true) {
            $record instanceof Product => $record->name,
            is_array($record) && is_string($record['name'] ?? null) => $record['name'],
            default => get_debug_type($record),
        };
    }
}
