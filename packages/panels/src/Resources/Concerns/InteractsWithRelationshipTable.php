<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Concerns;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Table;

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
    protected static string $relationship;

    protected static ?string $relationshipTitle = null;

    /**
     * @var class-string<Resource>|null
     */
    protected static ?string $relatedResource = null;

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
            throw new LogicException(sprintf('The relation manager [%s] names no relationship — set the $relationship static property to the owner association it manages.', static::class));
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
        return new Relationship($this->getOwnerRecord(), static::getRelationshipName());
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

    protected static function getHumanizedRelationshipName(): string
    {
        return u(static::getRelationshipName())->kebab()->replace('-', ' ')->title(allWords: true)->toString();
    }

    /**
     * The association seeds the table; a related resource stamps its labels and
     * then configures it, so a column the resource declares lands here too.
     */
    protected function makeTable(): Table
    {
        $table = Table::make($this)
            ->relationship(fn (): Relationship => $this->getRelationship());

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

    abstract protected function getResourceRegistry(): ResourceRegistry;

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
