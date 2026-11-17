<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use SortDirection;
use Sylament\Actions\AttachAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DetachAction;
use Sylament\Actions\DetachBulkAction;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table over one owner's to-many, named by its class, its identifier and the association,
 * titled by an attribute of the target, with `AttachAction` in the header, `DetachAction` on
 * each row and `DetachBulkAction` in the toolbar. Each variant configures one thing: the
 * attach select (`multiple`, `preloaded`, `noAttachAnother`, `limitedToTwo` and
 * `limitedToTwoInPlace`, which cap its options by returning the select and by returning
 * nothing, `swappedSelect`, which returns a select of its own, `permissive`,
 * which neither requires a pick nor refuses a label, `searchLatin`, `searchKeyed`,
 * `searchUnmapped`, `caseless`), its candidate query (`scoped` leaves Basil out, `replaced`
 * offers Rock alone, `selfOrdered` orders by descending id, the first two recording the
 * search they were handed in the {@see HookLog}), the titles (`tableTitleClosure` upper-cases
 * a plant's name, `actionTitleAttribute` gives the attach its own), the persistence steps
 * instead of writing (`recordingAttach` and `recordingBulk` record what they were handed,
 * `partialBulk` reports all but one record done), the bulk read (`noFetch`) and the rows
 * (`arrayRecords` lists plain arrays).
 */
#[AsLiveComponent(name: 'Tables:AttachableRelationTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class AttachableRelationTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    /**
     * @var class-string
     */
    #[LiveProp]
    public string $ownerClass = Garden::class;

    /**
     * @var array<string, int|string>
     */
    #[LiveProp]
    public array $ownerId = [];

    #[LiveProp]
    public string $relation = 'plants';

    #[LiveProp]
    public ?string $titleAttribute = 'name';

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    private HookLog $hookLog;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * The mount props naming a seeded owner by the identifier Doctrine reads off it, an
     * association half by the identifier of the record it holds.
     *
     * @param list<string> $variants
     *
     * @return array<string, mixed>
     */
    public static function props(EntityManagerInterface $entityManager, object $owner, string $relation, ?string $titleAttribute = 'name', array $variants = []): array
    {
        $identifier = array_map(
            static fn (mixed $value): mixed => is_object($value) ? current($entityManager->getClassMetadata($value::class)->getIdentifierValues($value)) : $value,
            $entityManager->getClassMetadata($owner::class)->getIdentifierValues($owner),
        );

        return ['ownerClass' => $owner::class, 'ownerId' => $identifier, 'relation' => $relation, 'titleAttribute' => $titleAttribute, 'variants' => $variants];
    }

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    /**
     * @return list<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        $table
            ->relationship(fn (): Relationship => new Relationship(
                $this->entityManager->find($this->ownerClass, $this->ownerId) ?? throw new LogicException('The owner is not in the database.'),
                $this->relation,
            ))
            ->recordTitleAttribute($this->titleAttribute)
            ->columns([
                TextColumn::make($this->titleAttribute ?? 'id'),
            ])
            ->headerActions([$this->attachAction()])
            ->recordActions([DetachAction::make()])
            ->toolbarActions([$this->detachBulkAction()]);

        if ($this->has('tableTitleClosure')) {
            $table->recordTitle(static fn (object $record): ?string => $record instanceof Plant ? mb_strtoupper($record->name) : null);
        }

        if ($this->has('arrayRecords')) {
            $table->records(static fn (): array => [['id' => 1, 'name' => 'Rock']]);
        }

        return $table;
    }

    private function attachAction(): AttachAction
    {
        $action = AttachAction::make()
            ->multiple($this->has('multiple'))
            ->preloadRecordSelect($this->has('preloaded'))
            ->attachAnother(!$this->has('noAttachAnother'));

        if ($this->has('limitedToTwo')) {
            $action->recordSelect(static fn (Select $select): Select => $select->optionsLimit(2));
        }

        if ($this->has('limitedToTwoInPlace')) {
            $action->recordSelect(static function (Select $select): void {
                $select->optionsLimit(2);
            });
        }

        if ($this->has('swappedSelect')) {
            $action->recordSelect(static fn (): Select => Select::make('recordId')->options(['spare' => 'Spare']));
        }

        if ($this->has('permissive')) {
            $action->recordSelect(static fn (Select $select): Select => $select
                ->required(false)
                ->getOptionLabelUsing(static fn (): string => 'Any')
                ->getOptionLabelsUsing(static fn (array $values): array => array_fill_keys(array_filter($values, static fn (mixed $value): bool => is_int($value) || is_string($value)), 'Any')));
        }

        foreach (['searchLatin' => ['latinName'], 'searchKeyed' => ['n' => 'latinName'], 'searchUnmapped' => ['colour']] as $variant => $columns) {
            if ($this->has($variant)) {
                $action->recordSelectSearchColumns($columns);
            }
        }

        if ($this->has('caseless')) {
            $action->forceSearchCaseInsensitive();
        }

        if ($this->has('scoped')) {
            $action->recordSelectOptionsQuery(function (QueryBuilder $query, ?string $search): void {
                $this->hookLog->record('options query: ' . ($search ?? 'null'));
                $query->andWhere("record.name <> 'Basil'");
            });
        }

        if ($this->has('replaced')) {
            $action->recordSelectOptionsQuery(function (?string $search): QueryBuilder {
                $this->hookLog->record('options query: ' . ($search ?? 'null'));

                return $this->entityManager->createQueryBuilder()->select('record')->from(Plant::class, 'record')->where("record.name = 'Rock'");
            });
        }

        if ($this->has('selfOrdered')) {
            $action->recordSelectOptionsQuery(static fn (QueryBuilder $query): QueryBuilder => $query->orderBy('record.id', SortDirection::Descending));
        }

        if ($this->has('actionTitleAttribute')) {
            $action->recordTitleAttribute('name');
        }

        if ($this->has('recordingAttach')) {
            $action->using(function (Relationship $relationship, array $records, ?object $record): void {
                $this->hookLog->record(sprintf(
                    'attach to %s: %s, bound: %s',
                    $relationship->name,
                    implode(', ', $this->sortedNames($records)),
                    $record instanceof Plant ? $record->name : 'none',
                ));
            });
        }

        return $action;
    }

    private function detachBulkAction(): DetachBulkAction
    {
        $action = DetachBulkAction::make()->fetchSelectedRecords(!$this->has('noFetch'));

        if ($this->has('recordingBulk')) {
            $action->using(function (array $records): void {
                $this->hookLog->record(sprintf('detach %d selected', count($records)));
            });
        }

        if ($this->has('partialBulk')) {
            $action->using(static function (DetachBulkAction $action, array $records): void {
                $action->reportBulkProcessingSuccessfulRecordsCount(count($records) - 1);
            });
        }

        return $action;
    }

    /**
     * @param array<mixed> $records
     *
     * @return list<string>
     */
    private function sortedNames(array $records): array
    {
        $names = array_map(static fn (mixed $record): string => $record instanceof Plant ? $record->name : get_debug_type($record), array_values($records));
        sort($names);

        return $names;
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
