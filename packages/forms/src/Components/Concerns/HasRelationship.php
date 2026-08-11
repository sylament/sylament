<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Stringable;
use Sylament\Schemas\State\AssociationTarget;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\wrap;

trait HasRelationship
{
    protected string|Closure|null $relationship = null;

    protected string|Closure|null $relationshipTitleAttribute = null;

    protected ?Closure $modifyRelationshipQueryUsing = null;

    /**
     * Back this field with a Doctrine association: the option map becomes the
     * target entity's table, `fill()` reads the association's key off the record,
     * and `getData()` writes the selection back onto it. `$name` names the
     * association property and defaults to the field's own name, so a field
     * carrying the association's name needs no argument at all.
     * `$modifyQueryUsing` scopes the option query — it receives the builder as
     * `$query` and returns it, or returns nothing to keep the one it was handed.
     */
    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null): static
    {
        $this->relationship = $name ?? $this->getName();
        $this->relationshipTitleAttribute = $titleAttribute;
        $this->modifyRelationshipQueryUsing = $modifyQueryUsing;

        $this->options(fn (): array => $this->getOptionsFromRelationship());

        $this->loadStateFromRelationshipsUsing(fn (object $record): mixed => $this->getStateFromRelationship($record));

        $this->saveRelationshipsUsing(function (object $record, mixed $state): void {
            $this->saveStateToRelationship($record, $state);
        });

        return $this;
    }

    public function hasRelationship(): bool
    {
        return filled($this->getRelationshipName());
    }

    public function getRelationshipName(): ?string
    {
        return $this->evaluate($this->relationship);
    }

    public function getRelationshipTitleAttribute(): ?string
    {
        return $this->evaluate($this->relationshipTitleAttribute);
    }

    /**
     * Every row of the association's target entity, keyed by the identifier the
     * client speaks and labelled by the declared title attribute. Always a full
     * map: an option list this field cannot build is an exception rather than an
     * empty array, because an empty enumeration would silently reject every
     * submitted value.
     *
     * @return array<string|int, string>
     */
    public function getOptionsFromRelationship(): array
    {
        $target = $this->getRelationshipTarget();
        $titleAttribute = $this->getRelationshipTitleAttributeOrFail();
        $reader = $this->getAssociationReader();
        $accessor = $this->getPropertyAccessor();

        $query = $reader->createOptionQuery($target->targetClass);

        if ($this->modifyRelationshipQueryUsing instanceof Closure) {
            $query = $this->scopeOptionQuery($query);
        }

        /** @var list<object> $records */
        $records = $query->getQuery()->getResult();

        $options = [];

        foreach ($records as $related) {
            $title = $accessor->getFromRecord($related, $titleAttribute);

            $options[$reader->getRecordKey($related)] = (is_scalar($title) || $title instanceof Stringable) ? (string) $title : '';
        }

        return $options;
    }

    /**
     * @throws LogicException when the field declares a relationship the mapping
     *                        cannot resolve — no entity manager, no record class,
     *                        or no association at that property
     */
    protected function getRelationshipTarget(): AssociationTarget
    {
        $name = $this->getRelationshipNameOrFail();
        $recordClass = $this->getRecordClass();
        $reader = $this->getAssociationReader();

        $target = ($recordClass === null) ? null : $reader->describe($recordClass, $name);

        if ($target instanceof AssociationTarget) {
            return $target;
        }

        throw new LogicException(sprintf(
            'The [%s] field declares relationship("%s"), but %s',
            (string) $this->getName(),
            $name,
            match (true) {
                ! $reader->hasEntityManager() => 'no Doctrine EntityManager backs it, so its options could only ever come back empty — and an empty option list silently rejects every submitted value. Register Doctrine (doctrine/doctrine-bundle), or replace relationship() with an options() map.',
                $recordClass === null => 'the schema names no record class to read the association off. Give the schema a record() instance or class-string, or replace relationship() with an options() map.',
                default => sprintf('[%s] maps no association at "%s". Name the property Doctrine maps, or replace relationship() with an options() map.', $recordClass, $name),
            },
        ));
    }

    /**
     * @throws LogicException when the field declares no relationship at all
     */
    protected function getRelationshipNameOrFail(): string
    {
        $name = $this->getRelationshipName();

        if (filled($name)) {
            return $name;
        }

        throw new LogicException(sprintf(
            'The [%s] field was asked for its relationship, but relationship() named none and the field carries no name to fall back on.',
            (string) $this->getName(),
        ));
    }

    /**
     * @throws LogicException when the relationship names no property to label its
     *                        options by
     */
    protected function getRelationshipTitleAttributeOrFail(): string
    {
        $titleAttribute = $this->getRelationshipTitleAttribute();

        if (filled($titleAttribute)) {
            return $titleAttribute;
        }

        throw new LogicException(sprintf(
            'The [%s] field declares relationship("%s") without a titleAttribute, so its options have nothing to be labelled by. Pass the property that labels the target entity as relationship()\'s second argument.',
            (string) $this->getName(),
            $this->getRelationshipNameOrFail(),
        ));
    }

    private function scopeOptionQuery(QueryBuilder $query): QueryBuilder
    {
        return $this->requireScopedOptionQuery($this->evaluate($this->modifyRelationshipQueryUsing, [
            'query' => $query,
        ], [
            QueryBuilder::class => $query,
        ]), $query);
    }

    /**
     * @throws LogicException when the scoping closure returned something other
     *                        than the builder it was handed
     */
    private function requireScopedOptionQuery(mixed $scoped, QueryBuilder $query): QueryBuilder
    {
        if ($scoped === null) {
            return $query;
        }

        if ($scoped instanceof QueryBuilder) {
            return $scoped;
        }

        throw new LogicException(sprintf(
            'The [%s] field\'s relationship modifyQueryUsing returned %s. Return the QueryBuilder it was handed, or nothing at all — anything else would drop the scoping without a trace.',
            (string) $this->getName(),
            get_debug_type($scoped),
        ));
    }

    /**
     * @return list<string>|string|null
     */
    private function getStateFromRelationship(object $record): array|string|null
    {
        $target = $this->getRelationshipTarget();
        $reader = $this->getAssociationReader();
        $related = $this->getPropertyAccessor()->getFromRecord($record, $this->getRelationshipNameOrFail());

        if (! $target->isToMany) {
            return is_object($related) ? $reader->getRecordKey($related) : null;
        }

        $keys = [];

        foreach (is_iterable($related) ? $related : [] as $item) {
            if (is_object($item)) {
                $keys[] = $reader->getRecordKey($item);
            }
        }

        return $keys;
    }

    private function saveStateToRelationship(object $record, mixed $state): void
    {
        $target = $this->getRelationshipTarget();
        $name = $this->getRelationshipNameOrFail();
        $reader = $this->getAssociationReader();
        $accessor = $this->getPropertyAccessor();

        if (! $target->isToMany) {
            $identifier = $this->toRelationshipIdentifier($state);

            $accessor->setOnRecord(
                $record,
                $name,
                ($identifier === null) ? null : $reader->getReference($target->targetClass, $identifier),
            );

            return;
        }

        $desired = [];

        foreach (wrap($state) as $item) {
            $identifier = $this->toRelationshipIdentifier($item);

            if ($identifier !== null) {
                $desired[] = $reader->getReference($target->targetClass, $identifier);
            }
        }

        $accessor->setAssociationOnRecord($record, $name, $desired);
    }

    /**
     * The submitted state as an entity identifier — null for an empty selection.
     *
     * @throws LogicException when the state is not something an identifier could
     *                        be, which validation against the option map should
     *                        already have refused
     */
    private function toRelationshipIdentifier(mixed $state): int|string|null
    {
        if (blank($state)) {
            return null;
        }

        if (is_int($state) || is_string($state)) {
            return $state;
        }

        throw new LogicException(sprintf(
            'The [%s] field submitted %s where the [%s] relationship expected an entity identifier.',
            (string) $this->getName(),
            get_debug_type($state),
            $this->getRelationshipNameOrFail(),
        ));
    }
}
