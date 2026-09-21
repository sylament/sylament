<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use ReflectionClass;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Table;
use Symfony\Contracts\Service\Attribute\Required;

use function Sylament\Support\filled;
use function Symfony\Component\String\u;

/**
 * One owner record's association as a table: the association it lists, the
 * title it carries, and — where the records also have a resource of their own —
 * the schemas, labels and page URLs that resource answers with. Composed by
 * both hosts of the tier, the embedded relation manager and the routed
 * `ManageRelatedRecords` page.
 */
trait InteractsWithRelationshipTable
{
    /*
     * The insert stays ungrouped, like the `insteadof` blocks in both hosts:
     * Rector splits a grouped `use` and drops the adaptations with it.
     */
    use HasTabs;

    protected static string $relationship;

    protected static ?string $relationshipTitle = null;

    /**
     * @var class-string<Resource>|null
     */
    protected static ?string $relatedResource = null;

    private ?EntityManagerInterface $relationshipTableEntityManager = null;

    #[Required]
    public function setRelationshipTableEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->relationshipTableEntityManager = $entityManager;
    }

    /**
     * @return class-string<Resource>|null
     */
    public static function getRelatedResource(): ?string
    {
        return static::$relatedResource;
    }

    public static function getRelationshipName(): string
    {
        if (! isset(static::$relationship)) {
            throw new LogicException(sprintf('The relationship table [%s] names no relationship — set the $relationship static property to the owner association it lists.', static::class));
        }

        return static::$relationship;
    }

    /**
     * The declared title, else the related resource's own plural label, else the
     * association name humanized.
     */
    public function getRelationshipTitle(): string
    {
        $title = static::$relationshipTitle;

        if (filled($title)) {
            return $title;
        }

        $relatedResource = static::getRelatedResource();

        if ($relatedResource !== null) {
            return $this->getResourceRegistry()->get($relatedResource)->getTitleCasePluralRecordLabel();
        }

        return static::getHumanizedRelationshipName();
    }

    public function getRelationship(): Relationship
    {
        $ownerRecord = $this->getOwnerRecord();

        $this->assertOwnerRecordIsScopeable($ownerRecord);

        return new Relationship($ownerRecord, static::getRelationshipName());
    }

    abstract public function getOwnerRecord(): object;

    public function form(Schema $schema): Schema
    {
        $relatedResource = static::getRelatedResource();

        if ($relatedResource === null) {
            return $schema;
        }

        return $this->getResourceRegistry()->get($relatedResource)->form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        $relatedResource = static::getRelatedResource();

        if ($relatedResource === null) {
            return $schema;
        }

        return $this->getResourceRegistry()->get($relatedResource)->infolist($schema);
    }

    /**
     * A related page of the records this table lists, carrying the owner record
     * where that page hangs under it. Null for a tier listing records no
     * resource speaks for — the host's own arms answer then.
     */
    public function getDefaultActionUrl(Action $action): ?string
    {
        $relatedResource = static::getRelatedResource();

        if ($relatedResource === null) {
            return null;
        }

        $resource = $this->getResourceRegistry()->get($relatedResource);
        $recordClass = $action->getRecordClass();

        if ($recordClass !== null && $recordClass !== $resource->getRecordClass()) {
            return null;
        }

        $parameters = $this->getRelatedResourceParentParameters($resource);

        if ($action instanceof CreateAction && $resource->hasPage('create')) {
            return $resource->getUrl('create', $parameters, shouldGuessMissingParameters: true);
        }

        if ($action instanceof EditAction && $resource->hasPage('edit') && $action->getRecord() !== null) {
            return $resource->getUrl('edit', [...$parameters, 'record' => $action->getRecord()], shouldGuessMissingParameters: true);
        }

        if ($action instanceof ViewAction && $resource->hasPage('view') && $action->getRecord() !== null) {
            return $resource->getUrl('view', [...$parameters, 'record' => $action->getRecord()], shouldGuessMissingParameters: true);
        }

        return null;
    }

    /**
     * The prefix this tier's page parameter takes, so two listings reached
     * through one URL never share a page number. Static by construction: a
     * `LiveProp` modifier reads it during hydration, where the table cannot be
     * built.
     */
    public function getTableQueryStringIdentifier(): ?string
    {
        return lcfirst(u(static::class)->afterLast('\\')->toString());
    }

    protected static function getHumanizedRelationshipName(): string
    {
        return u(static::getRelationshipName())->kebab()->replace('-', ' ')->title(allWords: true)->toString();
    }

    /**
     * The association seeds the table, the active tab scopes it and the row is
     * seeded to open the record — an action carrying no URL becomes the row's
     * click target, one carrying a URL becomes its link. A related resource
     * stamps its labels and then configures it, so a column the resource
     * declares lands here too.
     */
    protected function makeTable(): Table
    {
        $table = Table::make($this)
            ->relationship(fn (): Relationship => $this->getRelationship())
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
            ->recordAction(static function (object $record, Table $table): ?string {
                foreach (['view', 'edit'] as $name) {
                    $action = $table->getAction($name);

                    if (!$action instanceof Action) {
                        continue;
                    }

                    $action = static::stampRecordOnActionChain($action, $record);

                    if ($action->isHidden() || filled($action->getUrl())) {
                        continue;
                    }

                    return $action->getName();
                }

                return null;
            });

        if (!$table->hasCustomRecordUrl()) {
            $table->recordUrl(static function (object $record, Table $table): ?string {
                foreach (['view', 'edit'] as $name) {
                    $action = $table->getAction($name);

                    if (!$action instanceof Action) {
                        continue;
                    }

                    $action = static::stampRecordOnActionChain($action, $record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    $url = $action->getUrl();

                    if (filled($url)) {
                        return $url;
                    }
                }

                return null;
            });
        }

        $relatedResource = static::getRelatedResource();

        if ($relatedResource === null) {
            return $table;
        }

        $resource = $this->getResourceRegistry()->get($relatedResource);

        $table
            ->recordLabel($resource->getRecordLabel(...))
            ->pluralRecordLabel($resource->getPluralRecordLabel(...))
            ->recordTitleAttribute($resource->getRecordTitleAttribute(...))
            ->recordTitle($resource->getRecordTitle(...));

        return $resource->table($table);
    }

    /**
     * The record an action is asked about rides its whole group chain: a hidden
     * or URL arm reading the record through its group otherwise answers for the
     * row the chain last carried.
     */
    protected static function stampRecordOnActionChain(Action $action, object $record): Action
    {
        $clone = $action->getClone();
        $clone->record($record);

        $group = $clone->getGroup();

        while ($group instanceof ActionGroup) {
            $group->record($record);

            $group = $group->getGroup();
        }

        return $clone;
    }

    abstract protected function getResourceRegistry(): ResourceRegistry;

    /**
     * @throws LogicException where the owner record's class is keyed by anything
     *                        but one scalar identifier field
     */
    private function assertOwnerRecordIsScopeable(object $ownerRecord): void
    {
        $entityManager = $this->relationshipTableEntityManager;
        $ownerClass = $ownerRecord::class;

        // A host built outside the container and an owner Doctrine describes
        // nothing for are the relation layer's own refusals to make, in its own
        // words — this one only sorts the owners it *can* describe.
        if (! $entityManager instanceof EntityManagerInterface
            || $entityManager->getMetadataFactory()->isTransient($ownerClass)
            || new ReflectionClass($ownerRecord)->isAnonymous()) {
            return;
        }

        $metadata = $entityManager->getClassMetadata($ownerClass);

        if (! $metadata->isIdentifierComposite && ! $metadata->hasAssociation($metadata->getSingleIdentifierFieldName())) {
            return;
        }

        throw new LogicException(sprintf('The relationship table [%s] lists an association of [%s], whose records are not keyed by a single scalar identifier; scoping a relationship table to such an owner record is not built yet.', static::class, $ownerClass));
    }

    /**
     * The owner as the related resource's own parent parameter, where that
     * resource nests under a resource speaking for the owner. A related
     * resource nesting under nothing — or under something else — gets none.
     *
     * @return array<string, object>
     */
    private function getRelatedResourceParentParameters(Resource $resource): array
    {
        $registration = $resource->getParentResourceRegistration();

        if (! $registration instanceof ParentResourceRegistration) {
            return [];
        }

        $parentRecordClass = $this->getResourceRegistry()->get($registration->parentResource)->getRecordClass();
        $ownerRecord = $this->getOwnerRecord();

        if (! $ownerRecord instanceof $parentRecordClass) {
            return [];
        }

        return [$resource->getParentRouteParameterName() => $ownerRecord];
    }
}
