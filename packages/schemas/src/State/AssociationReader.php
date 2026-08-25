<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\InverseSideMapping;
use Doctrine\ORM\Mapping\OwningSideMapping;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use ReflectionClass;
use Stringable;
use Symfony\Component\PropertyAccess\PropertyAccess;

use function Sylament\Support\blank;

class AssociationReader
{
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
            $isNullable = $mapping->joinColumns[0]->nullable ?? true;
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
     * field, a many-to-many through a subselect over the owner's own association,
     * which needs no inverse mapping and adds no join to the outer query.
     * Unordered: the mapping's order is the caller's default sort, not the
     * query's.
     */
    public function createRelationshipQuery(Relationship $relationship, string $alias = 'record'): QueryBuilder
    {
        $target = $this->describeRelationship($relationship);
        $query = $this->createOptionQuery($target->targetClass, $alias);

        $query->andWhere($target->isManyToMany
            ? sprintf('%s.%s IN (%s)', $alias, $this->getIdentifierFieldName($target->targetClass), $this->getRelatedKeysSubselect($relationship, $target))
            : sprintf('%s.%s = :sylament_relationship_owner', $alias, $this->getMappedBy($relationship, $target)));

        return $query->setParameter('sylament_relationship_owner', $this->getRelationshipOwner($relationship));
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

        if ($target->isManyToMany) {
            $query->andWhere(sprintf('%s.%s NOT IN (%s)', $alias, $this->getIdentifierFieldName($target->targetClass), $this->getRelatedKeysSubselect($relationship, $target)));
        } else {
            $mappedBy = $this->getMappedBy($relationship, $target);

            $query->andWhere($query->expr()->orX(
                $query->expr()->isNull(sprintf('%s.%s', $alias, $mappedBy)),
                $query->expr()->neq(sprintf('%s.%s', $alias, $mappedBy), ':sylament_relationship_owner'),
            ));
        }

        return $query->setParameter('sylament_relationship_owner', $this->getRelationshipOwner($relationship));
    }

    /**
     * Make $related one of the owner's, in memory: a many-to-many adds it to the
     * collection Doctrine persists — the owner's, or the target's when the owner
     * holds the inverse side, since an inverse-side write is silently ignored at
     * flush — through the record's own adder; a one-to-many points the target's
     * mapped-by field at the owner. The caller flushes.
     */
    public function attach(Relationship $relationship, object $related): void
    {
        $target = $this->describeRelationship($relationship);

        if (! $target->isManyToMany) {
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
     * Whether a one-to-many's records may be dissociated at all: refused when the
     * mapping deletes an orphan (the owner owns these records — DeleteAction is
     * the action), when the target's join column is NOT NULL, or when its setter
     * or property does not accept null. A many-to-many always may.
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

        $owningSide = $this->describe($target->targetClass, $mappedBy);

        if ($owningSide instanceof AssociationTarget && ! $owningSide->isNullable) {
            throw new LogicException(sprintf('[%s::$%s] is mapped with a `nullable: false` join column, so a [%s] cannot be dissociated from its [%s]; make the column nullable or use DeleteAction.', $target->targetClass, $mappedBy, $target->targetClass, $ownerClass));
        }

        if (! $this->getPropertyAccessor()->acceptsNullOnRecord($target->targetClass, $mappedBy)) {
            throw new LogicException(sprintf('[%s::$%s] does not accept null — type its setter or property nullable so a record can be dissociated.', $target->targetClass, $mappedBy));
        }
    }

    /**
     * A managed reference to an entity, without loading it.
     *
     * @param class-string $class
     */
    public function getReference(string $class, int|string $id): object
    {
        return $this->requireEntityManager()->getReference($class, $id) ?? throw new LogicException(sprintf(
            'No [%s] carries the identifier [%s], so it cannot be referenced.',
            $class,
            $id,
        ));
    }

    /**
     * The string key an option list and the client speak an entity by, read off
     * the Doctrine mapping rather than a property convention.
     */
    public function getRecordKey(object $entity): string
    {
        return $this->findRecordKey($entity) ?? throw new LogicException(sprintf('Object records must expose a readable non-blank identifier; [%s] has none set.', $entity::class));
    }

    /**
     * The same key {@see getRecordKey()} answers, or null when the entity carries
     * no identifier yet — an unflushed record, which a caller that only wants to
     * exclude an existing row has nothing to exclude for.
     */
    public function findRecordKey(object $entity): ?string
    {
        $metadata = $this->requireEntityManager()->getClassMetadata($entity::class);

        if ($metadata->isIdentifierComposite) {
            throw new LogicException(sprintf('Records of [%s] carry a composite identifier, which a relation-backed field cannot key; use single-identifier entities.', $entity::class));
        }

        $identifierValues = $metadata->getIdentifierValues($entity);

        if ($identifierValues === []) {
            return null;
        }

        $key = array_first($identifierValues);

        if ($key instanceof Stringable) {
            $key = (string) $key;
        }

        $key = is_scalar($key) ? (string) $key : null;

        if (blank($key)) {
            throw new LogicException(sprintf('The identifier of [%s] is not a printable value.', $entity::class));
        }

        return $key;
    }

    /**
     * The field name a single-identifier entity's identifier is mapped under —
     * for a caller that needs the property name rather than its value.
     *
     * @param class-string $class
     */
    public function getIdentifierFieldName(string $class): string
    {
        $metadata = $this->requireEntityManager()->getClassMetadata($class);

        if ($metadata->isIdentifierComposite) {
            throw new LogicException(sprintf('Records of [%s] carry a composite identifier, which a relation-backed field cannot key; use single-identifier entities.', $class));
        }

        return $metadata->getSingleIdentifierFieldName();
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

    private function getRelatedKeysSubselect(Relationship $relationship, AssociationTarget $target): string
    {
        $ownerClass = $this->getEntityClass($relationship->owner);

        return sprintf(
            'SELECT sy_related.%s FROM %s sy_owner JOIN sy_owner.%s sy_related WHERE sy_owner.%s = :sylament_relationship_owner',
            $this->getIdentifierFieldName($target->targetClass),
            $ownerClass,
            $relationship->name,
            $this->getIdentifierFieldName($ownerClass),
        );
    }

    /**
     * @throws LogicException when a one-to-many mapping names no mapped-by field
     */
    private function getMappedBy(Relationship $relationship, AssociationTarget $target): string
    {
        return $target->mappedBy ?? throw new LogicException(sprintf('The one-to-many [%s] on [%s] names no mapped-by field, which Doctrine requires of every one-to-many.', $relationship->name, $relationship->owner::class));
    }

    /**
     * The owner itself as the bound parameter — Doctrine binds a managed entity
     * by its identifier, typed as the column is — after refusing an owner that
     * has none yet.
     */
    private function getRelationshipOwner(Relationship $relationship): object
    {
        $this->getRecordKey($relationship->owner);

        return $relationship->owner;
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
