<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Actions\AssociateAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DissociateAction;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table over one owner's one-to-many, named by its class, its identifier and the
 * association, titled by an attribute of the target, with `AssociateAction` in the header,
 * `DissociateAction` on each row and `DissociateBulkAction` in the toolbar. Each variant
 * configures one thing: the associate select (`multiple`, `singleByClosure`, which answers
 * `multiple()` through a closure answering false, `preloaded`, `noAssociateAnother`,
 * `limitedToOne` and `limitedToOneInPlace`, which cap its options by returning the select and
 * by returning nothing, `swappedSelect`, which returns a select of its own, `permissive`, which
 * neither requires a pick nor refuses a label, `searchContents`, `searchContentsKeyed`,
 * `searchUnmapped`, `caseless`), its candidate query (`scoped` leaves out every target titled
 * Nettle, recording the search it was handed in the {@see HookLog}, `selfOrdered` orders by
 * ascending id), the titles (`tableTitleClosure` upper-cases them through the table's
 * `recordTitle()`, `actionTitleClosure` prefixes them through the associate's own,
 * `actionTitleAttribute` gives the associate the table's attribute as its own), the persistence
 * steps instead of writing (`recordingAssociate` and `recordingBulk` record
 * what they were handed, `partialBulk` reports all but one record done), the bulk read
 * (`noFetch`), the rows (`arrayRecords` lists plain arrays) and the relationship itself
 * (`queryOnly` lists every target through `query()` and declares none).
 */
#[AsLiveComponent(name: 'Tables:AssociableRelationTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class AssociableRelationTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
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
    public string $relation = 'seedlings';

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
        if ($this->has('queryOnly')) {
            $target = $this->entityManager->getClassMetadata($this->ownerClass)->getAssociationTargetClass($this->relation);
            $table->query(fn (): QueryBuilder => $this->entityManager->createQueryBuilder()->select('record')->from($target, 'record'));
        } else {
            $table->relationship(fn (): Relationship => new Relationship(
                $this->entityManager->find($this->ownerClass, $this->ownerId) ?? throw new LogicException('The owner is not in the database.'),
                $this->relation,
            ));
        }

        $table
            ->recordTitleAttribute($this->titleAttribute)
            ->columns([
                TextColumn::make($this->titleAttribute ?? 'id'),
            ])
            ->headerActions([$this->associateAction()])
            ->recordActions([DissociateAction::make()])
            ->toolbarActions([$this->dissociateBulkAction()]);

        if ($this->has('tableTitleClosure')) {
            $table->recordTitle(fn (object $record): string => mb_strtoupper($this->title($record)));
        }

        if ($this->has('arrayRecords')) {
            $table->records(static fn (): array => [['id' => 1, 'name' => 'Bramble']]);
        }

        return $table;
    }

    private function associateAction(): AssociateAction
    {
        $action = AssociateAction::make()
            ->multiple($this->has('singleByClosure') ? static fn (): bool => false : $this->has('multiple'))
            ->preloadRecordSelect($this->has('preloaded'))
            ->associateAnother(!$this->has('noAssociateAnother'));

        if ($this->has('limitedToOne')) {
            $action->recordSelect(static fn (Select $select): Select => $select->optionsLimit(1));
        }

        if ($this->has('limitedToOneInPlace')) {
            $action->recordSelect(static function (Select $select): void {
                $select->optionsLimit(1);
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

        foreach (['searchContents' => ['contents'], 'searchContentsKeyed' => ['what' => 'contents'], 'searchUnmapped' => ['colour']] as $variant => $columns) {
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
                $query->andWhere("record.{$this->titleAttribute} <> 'Nettle'");
            });
        }

        if ($this->has('selfOrdered')) {
            $action->recordSelectOptionsQuery(static fn (QueryBuilder $query): QueryBuilder => $query->orderBy('record.id'));
        }

        if ($this->has('actionTitleClosure')) {
            $action->recordTitle(fn (object $record): string => 'Titled: ' . $this->title($record));
        }

        if ($this->has('actionTitleAttribute')) {
            $action->recordTitleAttribute($this->titleAttribute);
        }

        if ($this->has('recordingAssociate')) {
            $action->using(function (Relationship $relationship, object $record): void {
                $this->hookLog->record(sprintf('associate %s to %s', $this->title($record), $relationship->name));
            });
        }

        return $action;
    }

    private function dissociateBulkAction(): DissociateBulkAction
    {
        $action = DissociateBulkAction::make()->fetchSelectedRecords(!$this->has('noFetch'));

        if ($this->has('recordingBulk')) {
            $action->using(function (array $records): void {
                $names = array_map($this->title(...), array_values(array_filter($records, is_object(...))));
                sort($names);
                $this->hookLog->record('dissociate ' . implode(', ', $names));
            });
        }

        if ($this->has('partialBulk')) {
            $action->using(static function (DissociateBulkAction $action, array $records): void {
                $action->reportBulkProcessingSuccessfulRecordsCount(count($records) - 1);
            });
        }

        return $action;
    }

    private function title(object $record): string
    {
        $title = $this->titleAttribute === null ? null : ($record->{$this->titleAttribute} ?? null);

        return is_scalar($title) ? (string) $title : get_debug_type($record);
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
