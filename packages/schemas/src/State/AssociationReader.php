<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use BackedEnum;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\InverseSideMapping;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\OwningSideMapping;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use ReflectionClass;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\IdentifierFields;
use Sylament\Schemas\Identity\RecordKey;
use Symfony\Component\PropertyAccess\PropertyAccess;

use function Sylament\Support\blank;

class AssociationReader
{
    private const string TAKEN_IDENTIFIER = 'This value is already used.';

    private const string CHANGED_IDENTIFIER = 'This value cannot be changed.';

    private const string BLANK_IDENTIFIER = 'This value should not be blank.';

    public function __construct(
        private readonly ?EntityManagerInterface $entityManager = null,
        private ?PropertyAccessor $propertyAccessor = null,
    ) {
    }

    public function hasEntityManager(): bool
    {
        return $this->entityManager instanceof EntityManagerInterface;
    }

    /**
     * What the entity mapping declares at a property, or null when there is no
     * entity manager, the class is not a managed entity, or the property is not
     * an association — so a caller keeps its non-association behaviour.
     *
     * @param class-string $class
     */
    public function describe(string $class, string $property): ?AssociationTarget
    {
        if (!$this->entityManager instanceof EntityManagerInterface || $this->entityManager->getMetadataFactory()->isTransient($class)) {
            return null;
        }

        if (new ReflectionClass($class)->isAnonymous()) {
            return null;
        }

        $metadata = $this->entityManager->getClassMetadata($class);

        if (! $metadata->hasAssociation($property)) {
            return null;
        }

        $mapping = $metadata->getAssociationMapping($property);
        $isNullable = true;

        if ($mapping->isToOneOwningSide()) {
            $isNullable = array_all($mapping->joinColumns, static fn (JoinColumnMapping $joinColumn): bool => $joinColumn->nullable ?? true);
        }

        return new AssociationTarget(
            targetClass: $metadata->getAssociationTargetClass($property),
            isToMany: $metadata->isCollectionValuedAssociation($property),
            isOwningSide: ! $metadata->isAssociationInverseSide($property),
            isOrphanRemoval: $mapping->orphanRemoval,
            isManyToMany: $mapping->isManyToMany(),
            mappedBy: $mapping instanceof InverseSideMapping ? $mapping->mappedBy : null,
            inversedBy: $mapping instanceof OwningSideMapping ? $mapping->inversedBy : null,
            isNullable: $isNullable,
            orderBy: $mapping->isToMany() ? $mapping->orderBy() : [],
        );
    }

    /**
     * The mapping at a relationship's property, for a caller that lists or writes
     * the owner's collection — refused, rather than answered null, when nothing
     * backs it: no entity manager, an owner that is not a managed entity, a
     * property that is not an association, or a single-valued one, which is a
     * record rather than a collection to list.
     *
     * @throws LogicException when nothing backs the relationship
     */
    public function describeRelationship(Relationship $relationship): AssociationTarget
    {
        $ownerClass = $this->getEntityClass($relationship->owner);
        $target = $this->describe($ownerClass, $relationship->name);

        if (! $target instanceof AssociationTarget) {
            throw new LogicException(sprintf(
                'The relationship [%s] declared on [%s] cannot be read: that class maps no association at "%s" — name the property Doctrine maps.',
                $relationship->name,
                $ownerClass,
                $relationship->name,
            ));
        }

        if (! $target->isToMany) {
            throw new LogicException(sprintf(
                'The relationship [%s] declared on [%s] is single-valued; a relation-scoped table lists a collection. Declare a to-many association, or query() the related record yourself.',
                $relationship->name,
                $ownerClass,
            ));
        }

        return $target;
    }

    /**
     * The owner's related records — the target entity as the single root under
     * $alias, narrowed to the owner: a one-to-many through the target's mapped-by
     * field, a many-to-many through a correlated subselect over the owner's own
     * association, which needs no inverse mapping and adds no join to the outer
     * query — and so does either arm of an owner keyed by several fields, whose
     * mapped-by path DQL cannot compare. Unordered: the mapping's order is the
     * caller's default sort, not the query's.
     */
    public function createRelationshipQuery(Relationship $relationship, string $alias = 'record'): QueryBuilder
    {
        $target = $this->describeRelationship($relationship);
        $query = $this->createOptionQuery($target->targetClass, $alias);

        $query->andWhere($this->isScopedThroughOwner($relationship, $target)
            ? $this->getRelatedRecordsExists($query, $relationship, $target, $alias)
            : sprintf('%s.%s = :sylament_relationship_owner', $alias, $this->getMappedBy($relationship, $target)));

        return $this->bindRelationshipOwner($query, $relationship);
    }

    /**
     * Every target record NOT related to this owner — what an attach or an
     * associate offers. A one-to-many keeps the records that belong to nobody
     * and to somebody else (the SQL `<>` alone would drop the nulls).
     */
    public function createRelationshipCandidateQuery(Relationship $relationship, string $alias = 'record'): QueryBuilder
    {
        $target = $this->describeRelationship($relationship);
        $query = $this->createOptionQuery($target->targetClass, $alias);

        if (! $target->isManyToMany) {
            $this->assertRelationshipAcceptsAssociate($relationship);
        }

        if ($this->isScopedThroughOwner($relationship, $target)) {
            $query->andWhere(sprintf('NOT %s', $this->getRelatedRecordsExists($query, $relationship, $target, $alias)));
        } else {
            $mappedBy = $this->getMappedBy($relationship, $target);

            $query->andWhere($query->expr()->orX(
                $query->expr()->isNull(sprintf('%s.%s', $alias, $mappedBy)),
                $query->expr()->neq(sprintf('%s.%s', $alias, $mappedBy), ':sylament_relationship_owner'),
            ));
        }

        return $this->bindRelationshipOwner($query, $relationship);
    }

    /**
     * Make $related one of the owner's, in memory: a many-to-many adds it to the
     * collection Doctrine persists — the owner's, or the target's when the owner
     * holds the inverse side, since an inverse-side write is silently ignored at
     * flush — through the record's own adder; a one-to-many points the target's
     * mapped-by field at the owner, unless that write would move the record's
     * primary key. The caller flushes.
     */
    public function attach(Relationship $relationship, object $related): void
    {
        $target = $this->describeRelationship($relationship);

        if (! $target->isManyToMany) {
            $this->assertAttachKeepsIdentifier($relationship, $target, $related);
            $this->getPropertyAccessor()->setAssociationOnRecord($related, $this->getMappedBy($relationship, $target), $relationship->owner);

            return;
        }

        [$holder, $property, $member] = $target->isOwningSide
            ? [$relationship->owner, $relationship->name, $related]
            : [$related, $this->getMappedBy($relationship, $target), $relationship->owner];

        $current = $this->getCollectionMembers($holder, $property);

        if (in_array($member, $current, true)) {
            return;
        }

        $this->getPropertyAccessor()->setAssociationOnRecord($holder, $property, [...$current, $member]);
    }

    /**
     * Undo {@see attach()}: the mirror removal on the owning collection, or the
     * target's mapped-by field nulled — after {@see assertRelationshipAcceptsDetach()}.
     */
    public function detach(Relationship $relationship, object $related): void
    {
        $target = $this->describeRelationship($relationship);

        if (! $target->isManyToMany) {
            $this->assertRelationshipAcceptsDetach($relationship);
            $this->getPropertyAccessor()->setAssociationOnRecord($related, $this->getMappedBy($relationship, $target), null);

            return;
        }

        [$holder, $property, $member] = $target->isOwningSide
            ? [$relationship->owner, $relationship->name, $related]
            : [$related, $this->getMappedBy($relationship, $target), $relationship->owner];

        $this->getPropertyAccessor()->setAssociationOnRecord(
            $holder,
            $property,
            array_values(array_filter($this->getCollectionMembers($holder, $property), static fn (object $item): bool => $item !== $member)),
        );
    }

    /**
     * Whether an existing record may be associated into a one-to-many at all:
     * refused when the target's mapped-by field is part of its identifier, where
     * pointing it at this owner would move its primary key rather than move the
     * record. A many-to-many always may.
     *
     * @throws LogicException when associating would re-key the target
     */
    public function assertRelationshipAcceptsAssociate(Relationship $relationship): void
    {
        $target = $this->describeRelationship($relationship);

        if ($target->isManyToMany) {
            return;
        }

        $mappedBy = $this->getMappedBy($relationship, $target);

        if ($this->isIdentifierField($target->targetClass, $mappedBy)) {
            throw $this->refuseRekeyingAssociate($relationship, $target->targetClass, $mappedBy);
        }
    }

    /**
     * Whether a one-to-many's records may be dissociated at all: refused when the
     * mapping deletes an orphan (the owner owns these records — DeleteAction is
     * the action), when the target's mapped-by field is part of its identifier,
     * when its join column is NOT NULL, or when its setter or property does not
     * accept null. A many-to-many always may.
     *
     * @throws LogicException when the mapping forbids a dissociation
     */
    public function assertRelationshipAcceptsDetach(Relationship $relationship): void
    {
        $target = $this->describeRelationship($relationship);

        if ($target->isManyToMany) {
            return;
        }

        $mappedBy = $this->getMappedBy($relationship, $target);
        $ownerClass = $this->getEntityClass($relationship->owner);

        if ($target->isOrphanRemoval) {
            throw new LogicException(sprintf('[%s::$%s] declares orphanRemoval, so a record removed from it is deleted rather than dissociated — dissociating is not something the mapping permits; use DeleteAction.', $ownerClass, $relationship->name));
        }

        if ($this->isIdentifierField($target->targetClass, $mappedBy)) {
            throw new LogicException(sprintf('[%s::$%s] is part of the identifier of [%s], so a [%s] cannot be dissociated from its [%s] — nulling it would erase half the record\'s identity; use DeleteAction.', $target->targetClass, $mappedBy, $target->targetClass, $target->targetClass, $ownerClass));
        }

        $owningSide = $this->describe($target->targetClass, $mappedBy);

        if ($owningSide instanceof AssociationTarget && ! $owningSide->isNullable) {
            throw new LogicException(sprintf('[%s::$%s] is mapped with a `nullable: false` join column, so a [%s] cannot be dissociated from its [%s]; make the column nullable or use DeleteAction.', $target->targetClass, $mappedBy, $target->targetClass, $ownerClass));
        }

        if (! $this->getPropertyAccessor()->acceptsNullOnRecord($target->targetClass, $mappedBy)) {
            throw new LogicException(sprintf('[%s::$%s] does not accept null — type its setter or property nullable so a record can be dissociated.', $target->targetClass, $mappedBy));
        }
    }

    /**
     * A managed reference to an entity by the key {@see getRecordKey()} mints —
     * a key of any width, decoded against the class's own mapping before
     * anything is looked up, so a key no record of that class could mint is
     * refused without a statement. A well-formed key is looked up only where the
     * reference has to load; on a scalar identifier it becomes a lazy reference,
     * which names no record only once something initialises it.
     *
     * @param class-string $class
     *
     * @throws LogicException when the key is malformed for the class, or — where the reference loads — names no record
     */
    public function getReference(string $class, int|string $id): object
    {
        return $this->findReference($class, (string) $id) ?? throw new LogicException(sprintf(
            'No [%s] carries the identifier [%s], so it cannot be referenced.',
            $class,
            $id,
        ));
    }

    /**
     * The string key an option list and the client speak an entity by, read off
     * the Doctrine mapping rather than a property convention.
     *
     * @throws LogicException when the identifier is incomplete, or a component is not printable
     */
    public function getRecordKey(object $entity): string
    {
        return RecordKey::of($this->requireEntityManager(), $entity);
    }

    /**
     * The same key {@see getRecordKey()} answers, or null when the entity carries
     * no complete identifier yet — an unflushed record, which a caller that only
     * wants to exclude an existing row has nothing to exclude for.
     */
    public function findRecordKey(object $entity): ?string
    {
        return RecordKey::find($this->requireEntityManager(), $entity);
    }

    /**
     * Whether the record is missing an identifier component the application has
     * to assign — asked only of an identifier of more than one field, or one
     * carrying an association. A plain single scalar identifier is never refused
     * here: the relation layer has always keyed the class, and an application may
     * fill such a field from anywhere, including a listener no class-level mapping
     * shows.
     */
    public function hasIncompleteAssignedIdentifier(object $entity): bool
    {
        $metadata = $this->requireEntityManager()->getClassMetadata($entity::class);

        if ($this->isSingleScalarIdentifier($metadata)) {
            return false;
        }

        return $this->isMissingAssignedIdentifierComponent($entity);
    }

    /**
     * Whether a record's assigned identifier, as `persist()` left it — every
     * prePersist callback and listener run — still has a component that is
     * null or a blank string, an identifier association's own key included.
     * False for a generated identifier, for a single scalar one, and for an
     * identifier association onto a record whose generated identifier is still
     * pending, which Doctrine fills at the flush.
     */
    public function hasBlankAssignedIdentifier(object $record): bool
    {
        $entityManager = $this->requireEntityManager();
        $metadata = $entityManager->getClassMetadata($record::class);

        if (! $metadata->isIdentifierNatural() || $this->isSingleScalarIdentifier($metadata)) {
            return false;
        }

        return RecordKey::find($entityManager, $record) === null && ! $this->awaitsGeneratedIdentifier($record);
    }

    /**
     * Put back the identifier of every managed record this one's loaded
     * associations reach — itself first — whose key no longer prints the one the
     * unit of work keyed it by, and answer each such field: a flush would
     * otherwise move a primary key under a unit of work still keyed by the old
     * one, so the old key is put back on the spot and nothing flushed later
     * moves it. Runs no statement; a collection or reference nobody loaded holds
     * nothing a write touched, so it stays unloaded.
     *
     * @internal the record check behind a schema's getData() and an editable cell's write
     *
     * @return array<string, string> the refusal, keyed by the field's property path from the record
     */
    public function revertIdentifierChanges(object $record): array
    {
        $refusals = [];

        foreach ($this->walkLoadedRecords($record) as [$path, $current, $metadata]) {
            $unitOfWork = $this->requireEntityManager()->getUnitOfWork();

            if (! $unitOfWork->isInIdentityMap($current) || $unitOfWork->isScheduledForInsert($current) || $unitOfWork->isScheduledForDelete($current)) {
                continue;
            }

            foreach ($this->revertIdentifierChange($current, $metadata) as $field) {
                $refusals[$this->joinPropertyPath($path, $field)] = self::CHANGED_IDENTIFIER;
            }
        }

        return $refusals;
    }

    /**
     * Every new record this one's loaded cascade-persist associations reach —
     * itself first, then what the flush would insert with it — whose assigned
     * key a stored or managed record already holds, or a new record met earlier
     * in the same walk claims: `find()` per new record once its key is
     * complete, the identity map first and then one SELECT. The refusal lands
     * on the field the form lets the user type — the first identifier field
     * that is no association, else the first that does not point back at the
     * record the walk arrived from, else the first.
     *
     * @internal the record check behind a schema's getData(), after the record's own constraints pass
     *
     * @return array<string, string> the refusal, keyed by that field's property path from the record
     */
    public function findTakenIdentifiers(object $record): array
    {
        $claimed = [];
        $refusals = [];

        foreach ($this->walkLoadedRecords($record, cascadePersistOnly: true) as [$path, $current, $metadata, $backReference]) {
            $unitOfWork = $this->requireEntityManager()->getUnitOfWork();

            if (! $metadata->isIdentifierNatural() || ($unitOfWork->isInIdentityMap($current) && ! $unitOfWork->isScheduledForInsert($current))) {
                continue;
            }

            if ($this->isIdentifierTaken($current, $metadata, $claimed)) {
                $refusals[$this->joinPropertyPath($path, $this->getTakenIdentifierField($metadata, $backReference))] = self::TAKEN_IDENTIFIER;
            }
        }

        return $refusals;
    }

    /**
     * Every field a new record's blank assigned key could be refused under, in
     * the order a refusal picks from: each identifier field holding a blank
     * string — null, empty or only whitespace — then each identifier
     * association left unset or onto a record whose own key is incomplete;
     * the taken-key rule's field alone when neither finds one. Empty unless
     * {@see hasIncompleteAssignedIdentifier()} holds, so what that skips is
     * skipped here too, a single scalar identifier among it.
     *
     * @internal the record check behind a schema's getData(), which refuses under the first candidate the schema maps
     *
     * @return array<string, string> the refusal, keyed by each candidate's property path from the record, in that order
     */
    public function findBlankAssignedIdentifiers(object $record): array
    {
        if (! $this->entityManager instanceof EntityManagerInterface) {
            return [];
        }

        $metadata = $this->findEntityMetadata($this->entityManager, $record::class);

        if (! $metadata instanceof ClassMetadata || ! $metadata->isIdentifierNatural()) {
            return [];
        }

        $unitOfWork = $this->entityManager->getUnitOfWork();

        if (($unitOfWork->isInIdentityMap($record) && ! $unitOfWork->isScheduledForInsert($record)) || ! $this->hasIncompleteAssignedIdentifier($record)) {
            return [];
        }

        $candidates = [
            ...array_filter($metadata->identifier, static fn (string $field): bool => ! $metadata->hasAssociation($field) && blank($metadata->getFieldValue($record, $field))),
            ...array_filter($metadata->identifier, function (string $field) use ($metadata, $record): bool {
                if (! $metadata->hasAssociation($field)) {
                    return false;
                }

                $target = $metadata->getFieldValue($record, $field);

                return ! is_object($target) || $this->isMissingAssignedIdentifierComponent($target);
            }),
        ];

        return array_fill_keys($candidates === [] ? [$this->getTakenIdentifierField($metadata, null)] : $candidates, self::BLANK_IDENTIFIER);
    }

    /**
     * @param class-string $targetClass
     */
    public function createOptionQuery(string $targetClass, string $alias = 'record'): QueryBuilder
    {
        return $this->requireEntityManager()->createQueryBuilder()->select($alias)->from($targetClass, $alias);
    }

    public function remove(object $entity): void
    {
        $this->requireEntityManager()->remove($entity);
    }

    public function persist(object $entity): void
    {
        $this->requireEntityManager()->persist($entity);
    }

    public function flush(): void
    {
        $this->requireEntityManager()->flush();
    }

    /**
     * The record and every entity its loaded associations reach — or, for the
     * taken-key rule, only those a flush would cascade-persist — each once,
     * with its property path from the record and, for a member of an inverse
     * collection, the field that points back at the record it was reached
     * through. An uninitialized proxy or collection is left unloaded.
     *
     * @return iterable<array{string, object, ClassMetadata<object>, string|null}>
     */
    private function walkLoadedRecords(object $record, bool $cascadePersistOnly = false): iterable
    {
        if (! $this->entityManager instanceof EntityManagerInterface) {
            return;
        }

        $entityManager = $this->entityManager;
        $metadataByClass = [];
        $visited = [];
        $queue = [['', $record, null]];

        for ($index = 0; isset($queue[$index]); $index++) {
            [$path, $current, $backReference] = $queue[$index];

            if (isset($visited[spl_object_id($current)])) {
                continue;
            }

            $visited[spl_object_id($current)] = true;

            $metadata = $metadataByClass[$current::class] ??= $this->findEntityMetadata($entityManager, $current::class);

            if (! $metadata instanceof ClassMetadata || $entityManager->isUninitializedObject($current)) {
                continue;
            }

            yield [$path, $current, $metadata, $backReference];

            foreach ($metadata->associationMappings as $name => $mapping) {
                if ($cascadePersistOnly && ! $mapping->isCascadePersist()) {
                    continue;
                }

                $related = $metadata->getFieldValue($current, $name);
                $relatedPath = $this->joinPropertyPath($path, $name);
                $relatedBackReference = $mapping instanceof InverseSideMapping ? $mapping->mappedBy : null;

                if ($mapping->isToOne()) {
                    if (is_object($related)) {
                        $queue[] = [$relatedPath, $related, $relatedBackReference];
                    }

                    continue;
                }

                if (! is_iterable($related) || ($related instanceof PersistentCollection && ! $related->isInitialized())) {
                    continue;
                }

                foreach ($related as $key => $member) {
                    if (is_object($member) && (is_int($key) || is_string($key))) {
                        $queue[] = [sprintf('%s[%s]', $relatedPath, $key), $member, $relatedBackReference];
                    }
                }
            }
        }
    }

    /**
     * @param class-string $class
     *
     * @return ClassMetadata<object>|null
     */
    private function findEntityMetadata(EntityManagerInterface $entityManager, string $class): ?ClassMetadata
    {
        if (new ReflectionClass($class)->isAnonymous() || $entityManager->getMetadataFactory()->isTransient($class)) {
            return null;
        }

        return $entityManager->getClassMetadata($class);
    }

    /**
     * Whether a new record's assigned key is held already: by a new record
     * this walk met first — compared as the columns would store it, and an
     * association by the very object, the only identity a new target has — or
     * by the record `find()` answers, the identity map first and then one
     * SELECT. A key still missing a component, or holding a blank one, has
     * nothing to look up.
     *
     * @param ClassMetadata<object> $metadata
     * @param array<string, true>   $claimed
     */
    private function isIdentifierTaken(object $record, ClassMetadata $metadata, array &$claimed): bool
    {
        $entityManager = $this->requireEntityManager();
        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $parts = [];

        foreach ($metadata->identifier as $field) {
            $value = $metadata->getFieldValue($record, $field);

            if ($value === null || (is_string($value) && mb_trim($value) === '')) {
                return false;
            }

            if ($metadata->hasAssociation($field)) {
                $parts[] = is_object($value) ? 'o' . spl_object_id($value) : 'v' . serialize($value);

                continue;
            }

            $type = $metadata->getTypeOfField($field);
            $value = $value instanceof BackedEnum ? $value->value : $value;
            $parts[] = 'v' . serialize($type === null ? $value : Type::getType($type)->convertToDatabaseValue($value, $platform));
        }

        $claim = $metadata->rootEntityName . "\0" . implode("\0", $parts);

        if (isset($claimed[$claim])) {
            return true;
        }

        $claimed[$claim] = true;

        $values = RecordKey::values($entityManager, $record, $metadata->getName());

        if ($values === null || array_any($values, static fn (mixed $value): bool => is_string($value) && mb_trim($value) === '')) {
            return false;
        }

        $holder = $this->findWithFiltersSuspended($entityManager, $metadata->rootEntityName, array_combine($metadata->identifier, $values));

        return $holder !== null && $holder !== $record;
    }

    /**
     * `find()` past every enabled SQL filter — a primary key is unique whatever
     * a filter shows — restoring each filter afterwards, even when the lookup
     * throws. A holder the filters hid is loaded into the identity map for the
     * rest of the request, and its load listeners run.
     *
     * @param class-string         $class
     * @param array<string, mixed> $identifier
     */
    private function findWithFiltersSuspended(EntityManagerInterface $entityManager, string $class, array $identifier): ?object
    {
        if (! $entityManager->hasFilters()) {
            return $entityManager->find($class, $identifier);
        }

        $filters = $entityManager->getFilters();
        $suspended = array_keys($filters->getEnabledFilters());

        foreach ($suspended as $name) {
            $filters->suspend($name);
        }

        try {
            return $entityManager->find($class, $identifier);
        } finally {
            foreach ($suspended as $name) {
                $filters->restore($name);
            }
        }
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function getTakenIdentifierField(ClassMetadata $metadata, ?string $backReference): string
    {
        return array_find($metadata->identifier, static fn (string $field): bool => ! $metadata->hasAssociation($field))
            ?? array_find($metadata->identifier, static fn (string $field): bool => $field !== $backReference)
            ?? $metadata->identifier[0];
    }

    /**
     * Put back each identifier field of a managed record that no longer prints
     * the value the unit of work keyed it by — a scalar from that identifier,
     * an association from the original data — and answer those fields.
     *
     * @param ClassMetadata<object> $metadata
     *
     * @return list<string>
     */
    private function revertIdentifierChange(object $record, ClassMetadata $metadata): array
    {
        $entityManager = $this->requireEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $identifier = $unitOfWork->getEntityIdentifier($record);
        $changed = [];

        foreach ($metadata->identifier as $field) {
            $stored = $identifier[$field] ?? null;
            $current = $metadata->getFieldValue($record, $field);

            if ($metadata->hasAssociation($field)) {
                $current = is_object($current)
                    ? (RecordKey::values($entityManager, $current, $metadata->getAssociationTargetClass($field))[0] ?? null)
                    : null;
            }

            if ($stored === $current) {
                continue;
            }

            $printed = IdentifierFields::printable($stored);

            if ($printed !== null && $printed === IdentifierFields::printable($current)) {
                continue;
            }

            $changed[] = $field;
        }

        if ($changed === []) {
            return [];
        }

        $original = $unitOfWork->getOriginalEntityData($record);

        foreach ($changed as $field) {
            $metadata->setFieldValue($record, $field, $metadata->hasAssociation($field)
                ? ($original[$field] ?? throw new LogicException(sprintf('[%s::$%s] is an identifier association the unit of work holds no original value for, so its change cannot be put back.', $metadata->getName(), $field)))
                : $identifier[$field]);
        }

        return $changed;
    }

    private function joinPropertyPath(string $path, string $property): string
    {
        return $path === '' ? $property : $path . '.' . $property;
    }

    private function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor ??= new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());
    }

    /**
     * @return list<object>
     *
     * @throws LogicException when the mapped collection reads as a non-iterable
     */
    private function getCollectionMembers(object $holder, string $property): array
    {
        $members = $this->getPropertyAccessor()->getFromRecord($holder, $property);

        if (! is_iterable($members)) {
            throw new LogicException(sprintf('[%s::$%s] is mapped as a collection but reads as %s.', $holder::class, $property, get_debug_type($members)));
        }

        $list = [];

        foreach ($members as $member) {
            if (is_object($member)) {
                $list[] = $member;
            }
        }

        return $list;
    }

    /**
     * The record a key names, or null when the key is malformed for the class —
     * decoded first, so nothing is looked up — or when it names no row on the
     * loading route. A reference is built from the identifier values alone,
     * which an identifier field that is an association cannot be handed, so
     * those classes load; a scalar identifier keeps the reference's zero
     * statements.
     *
     * @param class-string $class
     */
    private function findReference(string $class, string $key): ?object
    {
        $entityManager = $this->requireEntityManager();
        $identifier = RecordKey::toIdentifier($entityManager, $class, $key);

        if ($identifier === null) {
            return null;
        }

        $metadata = $entityManager->getClassMetadata($class);

        return array_any($metadata->identifier, $metadata->hasAssociation(...))
            ? $entityManager->find($class, $identifier)
            : $entityManager->getReference($class, $identifier);
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function isSingleScalarIdentifier(ClassMetadata $metadata): bool
    {
        return count($metadata->identifier) === 1 && ! $metadata->hasAssociation($metadata->identifier[0]);
    }

    /**
     * Whether an identifier association, at any depth, points at a record
     * whose generated identifier is still unset.
     */
    private function awaitsGeneratedIdentifier(object $record): bool
    {
        $entityManager = $this->requireEntityManager();
        $metadata = $entityManager->getClassMetadata($record::class);

        foreach ($metadata->identifier as $field) {
            $target = $metadata->hasAssociation($field) ? $metadata->getFieldValue($record, $field) : null;

            if (! is_object($target)) {
                continue;
            }

            $targetMetadata = $entityManager->getClassMetadata($metadata->getAssociationTargetClass($field));

            if ($targetMetadata->usesIdGenerator() ? $targetMetadata->getIdentifierValues($target) === [] : $this->awaitsGeneratedIdentifier($target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@see hasIncompleteAssignedIdentifier()} without its entry bound, so that
     * behind an identifier association a single assigned scalar still counts. A
     * generated identifier that is still pending is Doctrine's to fill at flush,
     * and so is one the class assigns from a prePersist callback or a mapped
     * entity listener; none of the three is missing.
     */
    private function isMissingAssignedIdentifierComponent(object $entity): bool
    {
        $metadata = $this->requireEntityManager()->getClassMetadata($entity::class);

        if ($metadata->usesIdGenerator() || $metadata->hasLifecycleCallbacks(Events::prePersist) || isset($metadata->entityListeners[Events::prePersist])) {
            return false;
        }

        foreach ($metadata->identifier as $field) {
            $value = $metadata->getFieldValue($entity, $field);

            if ($metadata->hasAssociation($field)) {
                if (! is_object($value) || $this->isMissingAssignedIdentifierComponent($value)) {
                    return true;
                }

                continue;
            }

            if (blank($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The owner's own association, correlated back to the outer target row: a
     * subselect over the owner rather than a join, so the outer query keeps its
     * single root, and an identifier-wide correlation rather than a key list,
     * since comparing two identification variables compares their first
     * identifier column alone.
     */
    private function getRelatedRecordsExists(QueryBuilder $query, Relationship $relationship, AssociationTarget $target, string $alias): string
    {
        $ownerClass = $this->getEntityClass($relationship->owner);

        return sprintf(
            'EXISTS (SELECT 1 FROM %s sy_owner JOIN sy_owner.%s sy_related WHERE %s AND %s)',
            $ownerClass,
            $relationship->name,
            $this->getOwnerScope($query, $relationship, $ownerClass),
            IdentifierConstraint::correlate($query, 'sy_related', $target->targetClass, $alias),
        );
    }

    /**
     * Whether the narrowing is the correlated subselect rather than the target's
     * own mapped-by field: always for a many-to-many, and for a one-to-many whose
     * owner is keyed by several fields.
     */
    private function isScopedThroughOwner(Relationship $relationship, AssociationTarget $target): bool
    {
        return $target->isManyToMany || $this->hasCompositeOwner($relationship);
    }

    private function hasCompositeOwner(Relationship $relationship): bool
    {
        return $this->requireEntityManager()->getClassMetadata($this->getEntityClass($relationship->owner))->isIdentifierComposite;
    }

    /**
     * The subselect's owner narrowed to this one. An owner keyed by several
     * fields binds itself here, one typed parameter per field, through the key it
     * mints; a single-identifier owner is bound by {@see bindRelationshipOwner()}.
     *
     * @param class-string $ownerClass
     *
     * @throws LogicException when the owner's own key does not decode, since a dropped scope would list every owner's records
     */
    private function getOwnerScope(QueryBuilder $query, Relationship $relationship, string $ownerClass): string
    {
        if (! $this->hasCompositeOwner($relationship)) {
            return sprintf('sy_owner.%s = :sylament_relationship_owner', $this->getOwnerIdentifierField($ownerClass));
        }

        $key = $this->getRecordKey($relationship->owner);

        return IdentifierConstraint::equals($query, 'sy_owner', $ownerClass, $key, 'sylament_relationship_owner')
            ?? throw new LogicException(sprintf('The owner [%s] mints the key [%s], which does not decode against its own mapping, so the relationship [%s] cannot be scoped to it — an identifier type that cannot read its own printed form.', $ownerClass, $key, $relationship->name));
    }

    /**
     * @param class-string $ownerClass
     */
    private function getOwnerIdentifierField(string $ownerClass): string
    {
        return $this->requireEntityManager()->getClassMetadata($ownerClass)->identifier[0];
    }

    /**
     * @param class-string $class
     */
    private function isIdentifierField(string $class, string $field): bool
    {
        return in_array($field, $this->requireEntityManager()->getClassMetadata($class)->identifier, true);
    }

    /**
     * Refuse the one write the relation layer never makes: an existing record
     * whose mapped-by field is part of its identifier, already pointing at
     * another owner. A record the entity manager does not manage yet has no
     * primary key to move — it is written under this owner, which is what a
     * create form building its child does — and so is one pointing at nobody or
     * at this owner.
     *
     * @throws LogicException when the write would move the target's primary key
     */
    private function assertAttachKeepsIdentifier(Relationship $relationship, AssociationTarget $target, object $related): void
    {
        $mappedBy = $this->getMappedBy($relationship, $target);

        if (! $this->isIdentifierField($target->targetClass, $mappedBy) || ! $this->requireEntityManager()->contains($related)) {
            return;
        }

        $current = $this->requireEntityManager()->getClassMetadata($target->targetClass)->getFieldValue($related, $mappedBy);

        if (is_object($current) && $current !== $relationship->owner) {
            throw $this->refuseRekeyingAssociate($relationship, $target->targetClass, $mappedBy);
        }
    }

    /**
     * @param class-string $targetClass
     */
    private function refuseRekeyingAssociate(Relationship $relationship, string $targetClass, string $mappedBy): LogicException
    {
        return new LogicException(sprintf(
            '[%s::$%s] is part of the identifier of [%s], so an existing record cannot be associated into [%s::$%s] — pointing it at another owner would move its primary key. Create the record under this owner instead, or move the association out of the identifier.',
            $targetClass,
            $mappedBy,
            $targetClass,
            $this->getEntityClass($relationship->owner),
            $relationship->name,
        ));
    }

    /**
     * @throws LogicException when a one-to-many mapping names no mapped-by field
     */
    private function getMappedBy(Relationship $relationship, AssociationTarget $target): string
    {
        return $target->mappedBy ?? throw new LogicException(sprintf('The one-to-many [%s] on [%s] names no mapped-by field, which Doctrine requires of every one-to-many.', $relationship->name, $relationship->owner::class));
    }

    /**
     * The single-identifier owner's parameter, after refusing an owner that has
     * no identifier yet: its identifier value — one that is an association
     * reduced to its target's, at any depth — bound under that terminal field's
     * DBAL type, because Doctrine binds an entity by casting its identifier and
     * never converts it. An owner keyed by several fields bound itself while its
     * scope was composed.
     */
    private function bindRelationshipOwner(QueryBuilder $query, Relationship $relationship): QueryBuilder
    {
        if ($this->hasCompositeOwner($relationship)) {
            return $query;
        }

        $entityManager = $this->requireEntityManager();
        $ownerClass = $this->getEntityClass($relationship->owner);

        $this->getRecordKey($relationship->owner);

        $values = RecordKey::values($entityManager, $relationship->owner, $ownerClass)
            ?? throw new LogicException(sprintf('Object records must expose a readable non-blank identifier; [%s] has none set.', $relationship->owner::class));

        return $query->setParameter('sylament_relationship_owner', $values[0], IdentifierFields::of($entityManager, $ownerClass)->types[0]);
    }

    /**
     * The mapped class behind an object — a Doctrine proxy answers its entity's
     * name, not its own — or a refusal naming the object when it is not managed.
     *
     * @return class-string
     *
     * @throws LogicException when the object's class is not a mapped entity
     */
    private function getEntityClass(object $entity): string
    {
        $entityManager = $this->requireEntityManager();

        if ($entityManager->getMetadataFactory()->isTransient($entity::class)) {
            throw new LogicException(sprintf('[%s] is not a managed entity, so it cannot own a relationship.', $entity::class));
        }

        return $entityManager->getClassMetadata($entity::class)->getName();
    }

    private function requireEntityManager(): EntityManagerInterface
    {
        return $this->entityManager ?? throw new LogicException(
            'The sylament AssociationReader has no Doctrine EntityManager, so this operation cannot run — it was built outside the container, or no EntityManagerInterface service exists. Register Doctrine (doctrine/doctrine-bundle) or bind the record by hand after getData().',
        );
    }
}
