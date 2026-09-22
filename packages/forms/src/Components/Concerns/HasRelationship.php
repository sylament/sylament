<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Stringable;
use Sylament\Schemas\State\AssociationTarget;
use Sylament\Schemas\State\Relationship;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\wrap;

trait HasRelationship
{
    use BelongsToRelationship;

    protected string|Closure|null $relationshipTitleAttribute = null;

    protected ?Closure $modifyRelationshipQueryUsing = null;

    protected string|Markup|Closure|null $getOptionLabelFromRecordUsing = null;

    /**
     * @var array<string|int, string|Markup>|null
     */
    protected ?array $cachedRelationshipOptions = null;

    /**
     * Back this field with a Doctrine association: the option map becomes the
     * target entity's table, `fill()` reads the association's key off the record,
     * and `getData()` writes the selection back onto it. `$name` names the
     * association property and defaults to the field's own name, so a field
     * carrying the association's name needs no argument at all.
     * `$modifyQueryUsing` scopes the option query — it receives the builder as
     * `$query` (and, from a search arm, the term as `$search`, `null` elsewhere)
     * and returns it, or returns nothing to keep the one it was handed.
     */
    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null): static
    {
        $this->relationship = $name ?? $this->getName();
        $this->relationshipTitleAttribute = $titleAttribute;
        $this->modifyRelationshipQueryUsing = $modifyQueryUsing;
        $this->cachedRelationshipOptions = null;

        $this->options(static fn (self $component): array => $component->getOptionsFromRelationship());

        $this->loadStateFromRelationshipsUsing(static fn (self $component, object $record): mixed => $component->getStateFromRelationship($record));

        $this->saveRelationshipsUsing(static function (self $component, object $record, mixed $state): void {
            $component->saveStateToRelationship($record, $state);
        });

        return $this;
    }

    public function getRelationshipTitleAttribute(): ?string
    {
        return $this->evaluate($this->relationshipTitleAttribute);
    }

    /**
     * Drop the memoized option map, so the next enumeration goes back to the
     * database. A write that adds or renames a target row has to call this, or
     * the field validates the new key against the map it built before the write.
     */
    public function forgetCachedRelationshipOptions(): void
    {
        $this->cachedRelationshipOptions = null;
    }

    public function getOptionLabelFromRecordUsing(?Closure $callback): static
    {
        $this->getOptionLabelFromRecordUsing = $callback;
        $this->cachedRelationshipOptions = null;

        return $this;
    }

    public function hasOptionLabelFromRecordUsingCallback(): bool
    {
        return $this->getOptionLabelFromRecordUsing !== null;
    }

    /**
     * The option label a record carries, empty where no callback answers — the
     * same coercion the title-attribute arm makes for an unreadable title.
     */
    public function getOptionLabelFromRecord(object $record): string|Markup
    {
        return $this->evaluate($this->getOptionLabelFromRecordUsing, [
            'record' => $record,
        ], [
            $record::class => $record,
        ]) ?? '';
    }

    /**
     * Every row of the association's target entity the declared query scope
     * admits, keyed by the identifier the client speaks and labelled by the
     * record-label callback or, without one, the title attribute. A map this
     * field cannot build at all is an exception rather than an empty array,
     * because an empty enumeration would silently reject every submitted value;
     * a composing field may still answer `[]` by design where the whole map is
     * not the truth (a searchable, non-preloaded `Select` enumerates nothing
     * until the user types). The map is memoized for the field's lifetime unless
     * a query scope is declared, whose closure may read state that changes within
     * the request. The title attribute only ever produces text, but the
     * record-label callback may answer `Markup`, so the map is typed for both.
     *
     * @return array<string|int, string|Markup>
     */
    public function getOptionsFromRelationship(): array
    {
        if ($this->modifyRelationshipQueryUsing === null && $this->cachedRelationshipOptions !== null) {
            return $this->cachedRelationshipOptions;
        }

        $options = $this->getOptionsFromRecords($this->getRecordsFromRelationship());

        if ($this->modifyRelationshipQueryUsing === null) {
            $this->cachedRelationshipOptions = $options;
        }

        return $options;
    }

    /**
     * Every row an option could be built from: the association's target table,
     * scoped by the declared query modifier. Unmemoized — the rows are a step, and
     * each caller memoizes the option map it derives from them instead.
     *
     * @return list<object>
     */
    protected function getRecordsFromRelationship(): array
    {
        /** @var list<object> */
        return $this->createScopedOptionQuery()->getQuery()->getResult();
    }

    /**
     * The option query before it runs — `SELECT record FROM <target> record`
     * over the multiplicity-matched association, scoped by the declared query
     * modifier — for a caller that constrains, limits or orders it first. The
     * modifier receives the search term a search arm passes as `$search`, and
     * `null` everywhere else.
     */
    protected function createScopedOptionQuery(?string $search = null): QueryBuilder
    {
        $target = $this->getMultiplicityMatchedRelationshipTarget();

        $query = $this->getAssociationReader()->createOptionQuery($target->targetClass);

        if ($this->modifyRelationshipQueryUsing instanceof Closure) {
            return $this->scopeOptionQuery($query, $search);
        }

        return $query;
    }

    /**
     * The option map a list of target rows makes: each row's record key mapped to
     * its label.
     *
     * @param list<object> $records
     *
     * @return array<string|int, string|Markup>
     */
    protected function getOptionsFromRecords(array $records): array
    {
        $reader = $this->getAssociationReader();

        $options = [];

        foreach ($records as $record) {
            $options[$reader->getRecordKey($record)] = $this->resolveOptionLabelFromRecord($record);
        }

        return $options;
    }

    /**
     * The label one target row carries: what the record-label callback answers
     * when one is declared, else the title attribute read off the row as text —
     * an unreadable title is `''`, never a throw, because a row is not an option
     * the user misconfigured.
     */
    protected function resolveOptionLabelFromRecord(object $record): string|Markup
    {
        if ($this->hasOptionLabelFromRecordUsingCallback()) {
            return $this->getOptionLabelFromRecord($record);
        }

        $title = $this->getPropertyAccessor()->getFromRecord($record, $this->getRelationshipTitleAttributeOrFail());

        return (is_scalar($title) || $title instanceof Stringable) ? (string) $title : '';
    }

    protected function getMissingEntityManagerConsequence(): string
    {
        return 'its options could only ever come back empty — and an empty option list silently rejects every submitted value';
    }

    protected function getRelationshipFallbackAdvice(): string
    {
        return 'or replace relationship() with an options() map.';
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

    /**
     * What the mapping declares at the association, refused when its multiplicity
     * and `multiple()` disagree: a select speaks one value or a list, and the
     * association holds one record or a collection, so the two declarations have
     * to name the same shape. Resolved on every read, never snapshotted, so
     * `multiple()` may be declared on either side of `relationship()`.
     *
     * @throws LogicException when the association holds a collection the field
     *                        never declared `multiple()` for, or a single record
     *                        it did
     */
    private function getMultiplicityMatchedRelationshipTarget(): AssociationTarget
    {
        $target = $this->getRelationshipTarget();
        $isMultiple = $this->isMultiple();

        if ($target->isToMany && ! $isMultiple) {
            throw new LogicException(sprintf(
                'The [%s] field declares relationship("%s"), but that association holds a collection of %s while the field speaks a single value — every selection but one would be dropped. Call multiple() on this field, or point relationship() at a to-one association.',
                (string) $this->getName(),
                $this->getRelationshipNameOrFail(),
                $target->targetClass,
            ));
        }

        if (! $target->isToMany && $isMultiple) {
            throw new LogicException(sprintf(
                'The [%s] field declares relationship("%s") alongside multiple(), but that association holds a single %s rather than a collection, so a list of selections could not map it. Drop multiple(), or point relationship() at a to-many association.',
                (string) $this->getName(),
                $this->getRelationshipNameOrFail(),
                $target->targetClass,
            ));
        }

        return $target;
    }

    private function scopeOptionQuery(QueryBuilder $query, ?string $search): QueryBuilder
    {
        return $this->requireScopedOptionQuery($this->evaluate($this->modifyRelationshipQueryUsing, [
            'query' => $query,
            'search' => $search,
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
        $target = $this->getMultiplicityMatchedRelationshipTarget();
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
        $target = $this->getMultiplicityMatchedRelationshipTarget();
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

        $reader->assertRelationshipAcceptsAssociate(new Relationship($record, $name));

        $desired = [];

        foreach (wrap($state) as $item) {
            $identifier = $this->toRelationshipIdentifier($item);

            if ($identifier !== null) {
                $desired[] = $reader->getReference($target->targetClass, $identifier);
            }
        }

        if ($target->isManyToMany && ! $target->isOwningSide) {
            $this->writeDesiredToOwningSide($target, $record, $name, $desired);
        }

        $accessor->setAssociationOnRecord($record, $name, $desired);
    }

    /**
     * Write the desired list onto the association's owning side, the only side
     * Doctrine persists: an inverse-side collection is ignored at flush, so
     * writing it alone reports success and writes no join row. Every
     * member no longer desired is detached and every new one attached, both
     * through the reader, which flips to the target's own collection — so the
     * kept members' join rows are never touched.
     *
     * @param list<object> $desired
     *
     * @throws LogicException when removing a member would delete the record being saved
     */
    private function writeDesiredToOwningSide(AssociationTarget $target, object $record, string $name, array $desired): void
    {
        $reader = $this->getAssociationReader();
        $relationship = new Relationship($record, $name);
        $members = $this->getPropertyAccessor()->getFromRecord($record, $name);

        $current = [];

        foreach (is_iterable($members) ? $members : [] as $member) {
            if (is_object($member)) {
                $current[] = $member;
            }
        }

        $removed = array_values(array_filter($current, static fn (object $member): bool => ! in_array($member, $desired, true)));

        if ($removed !== []) {
            $this->assertOwningSideKeepsTheRecord($target);
        }

        foreach ($removed as $member) {
            $reader->detach($relationship, $member);
        }

        foreach ($desired as $member) {
            if (! in_array($member, $current, true)) {
                $reader->attach($relationship, $member);
            }
        }
    }

    /**
     * Refuse the one write that would delete the record it is saving: the
     * owning collection declares orphanRemoval, so Doctrine holds its members
     * privately owned and deletes the one taken out of it — and from the
     * inverse side that one is this record. The reader refuses the same shape
     * on a one-to-many by name; a many-to-many can only reach it from here.
     *
     * @throws LogicException when the owning side deletes what it drops
     */
    private function assertOwningSideKeepsTheRecord(AssociationTarget $target): void
    {
        $mappedBy = $target->mappedBy;

        if ($mappedBy === null) {
            return;
        }

        $owningSide = $this->getAssociationReader()->describe($target->targetClass, $mappedBy);

        if ($owningSide instanceof AssociationTarget && $owningSide->isOrphanRemoval) {
            throw new LogicException(sprintf(
                '[%s::$%s] declares orphanRemoval, so a member dropped from it is deleted rather than unlinked — deselecting one in the [%s] field would delete the record this form is saving rather than its join row. Put the field on the owning side of the association, or drop orphanRemoval.',
                $target->targetClass,
                $mappedBy,
                (string) $this->getName(),
            ));
        }
    }

    /**
     * The submitted state as an entity identifier — null for an empty selection.
     *
     * @throws LogicException when the state is not something an identifier could
     *                        be, which validation against the option map should
     *                        already have refused
     */
    private function toRelationshipIdentifier(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_int($state) || is_string($state)) {
            return (string) $state;
        }

        throw new LogicException(sprintf(
            'The [%s] field submitted %s where the [%s] relationship expected an entity identifier.',
            (string) $this->getName(),
            get_debug_type($state),
            $this->getRelationshipNameOrFail(),
        ));
    }
}
