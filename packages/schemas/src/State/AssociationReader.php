<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use ReflectionClass;
use Stringable;

use function Sylament\Support\blank;

class AssociationReader
{
    public function __construct(
        private readonly ?EntityManagerInterface $entityManager = null,
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

        return new AssociationTarget(
            targetClass: $metadata->getAssociationTargetClass($property),
            isToMany: $metadata->isCollectionValuedAssociation($property),
            isOwningSide: ! $metadata->isAssociationInverseSide($property),
            isOrphanRemoval: $metadata->getAssociationMapping($property)->orphanRemoval,
        );
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
        $metadata = $this->requireEntityManager()->getClassMetadata($entity::class);

        if ($metadata->isIdentifierComposite) {
            throw new LogicException(sprintf('Records of [%s] carry a composite identifier, which a relation-backed field cannot key; use single-identifier entities.', $entity::class));
        }

        $identifierValues = $metadata->getIdentifierValues($entity);

        if ($identifierValues === []) {
            throw new LogicException(sprintf('Object records must expose a readable non-blank identifier; [%s] has none set.', $entity::class));
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

        try {
            return $metadata->getSingleIdentifierFieldName();
        } catch (MappingException) {
            throw new LogicException(sprintf('Records of [%s] carry a composite identifier, which a relation-backed field cannot key; use single-identifier entities.', $class));
        }
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

    private function requireEntityManager(): EntityManagerInterface
    {
        return $this->entityManager ?? throw new LogicException(
            'The sylament AssociationReader has no Doctrine EntityManager, so this operation cannot run — it was built outside the container, or no EntityManagerInterface service exists. Register Doctrine (doctrine/doctrine-bundle) or bind the record by hand after getData().',
        );
    }
}
