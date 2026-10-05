<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Resource\Concerns;

use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\State\AssociationTarget;
use Sylament\Schemas\State\Relationship;

use function Sylament\Support\filled;
use function Symfony\Component\String\u;

trait BelongsToParent
{
    /**
     * @var class-string<Resource>|null
     */
    protected static ?string $parentResource = null;

    private ?ParentResourceRegistration $parentResourceRegistration = null;

    private ?string $parentRelationshipName = null;

    /**
     * @return class-string<Resource>|null
     */
    public function getParentResource(): ?string
    {
        return static::$parentResource;
    }

    /**
     * This resource as the parent of another — the registration a child resource
     * returns from {@see getParentResourceRegistration()} when the mapping alone
     * cannot name the association.
     *
     * @param class-string<Resource> $childResource
     */
    public function asParent(string $childResource): ParentResourceRegistration
    {
        return new ParentResourceRegistration(static::class, $childResource);
    }

    public function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        if ($this->parentResourceRegistration instanceof ParentResourceRegistration) {
            return $this->parentResourceRegistration;
        }

        $parentResource = $this->getParentResource();

        if ($parentResource === null) {
            return null;
        }

        return $this->parentResourceRegistration = new ParentResourceRegistration($parentResource, static::class);
    }

    /**
     * The parent's association these records hang off: the name the registration
     * carries, or the one to-many association on the parent record that targets
     * this resource's record.
     *
     * @throws LogicException when no single association can be derived
     */
    public function getParentRelationshipName(): string
    {
        if ($this->parentRelationshipName !== null) {
            return $this->parentRelationshipName;
        }

        $explicitName = $this->getParentRegistrationOrFail()->getRelationshipName();

        if ($explicitName !== null) {
            return $this->parentRelationshipName = $explicitName;
        }

        $parentRecordClass = $this->getParentRecordClass();
        $recordClass = $this->getRecordClass();
        $candidates = [];

        foreach ($this->getEntityManager()->getClassMetadata($parentRecordClass)->getAssociationMappings() as $mapping) {
            if ($mapping->isToMany() && $mapping->targetEntity === $recordClass) {
                $candidates[] = $mapping->fieldName;
            }
        }

        if (count($candidates) !== 1) {
            throw new LogicException(sprintf('Resource [%s] cannot derive its parent relationship from [%s]: %s association(s) target [%s]. Override getParentResourceRegistration() and call ->relationship() naming the association.', static::class, $parentRecordClass, count($candidates), $recordClass));
        }

        return $this->parentRelationshipName = $candidates[0];
    }

    /**
     * The route parameter the parent record travels under: the field completing
     * the association on this side, or the parent record's snake-cased basename
     * where the mapping names none.
     */
    public function getParentRouteParameterName(): string
    {
        $parentRecordClass = $this->getParentRecordClass();
        $target = $this->getAssociationReader()->describe($parentRecordClass, $this->getParentRelationshipName());
        $completingField = $target instanceof AssociationTarget ? $target->mappedBy ?? $target->inversedBy : null;

        return $completingField ?? u($parentRecordClass)->afterLast('\\')->snake()->toString();
    }

    /**
     * Make a record one of the parent's, in memory — the caller flushes.
     */
    public function attachRecordToParent(object $record, object $parentRecord): void
    {
        $this->getAssociationReader()->attach(new Relationship($parentRecord, $this->getParentRelationshipName()), $record);
    }

    /**
     * This resource's own record query narrowed to one parent record under its
     * root alias, so every condition an overridden {@see getRecordQuery()}
     * carries holds beside the parent's — the scope every nested page and
     * binding resolves through.
     *
     * @throws LogicException when the record query names no root alias
     */
    public function getParentScopedRecordQuery(object $parentRecord): QueryBuilder
    {
        $query = clone $this->getRecordQuery();
        $alias = $query->getRootAliases()[0]
            ?? throw new LogicException(sprintf('The record query of resource [%s] names no root alias to scope to its parent record.', static::class));

        return $this->getAssociationReader()->scopeToRelationship($query, new Relationship($parentRecord, $this->getParentRelationshipName()), $alias);
    }

    /**
     * The ancestors above this resource, nearest parent first: the route
     * parameter each travels under, the resource speaking for it, and the
     * property on the level below pointing at it — null where the mapping has
     * none to read, which is where a guess stops.
     *
     * @return list<array{parameter: string, resource: Resource, property: string|null}>
     */
    private function getAncestorChain(): array
    {
        $chain = [];
        $child = $this;

        while (($registration = $child->getParentResourceRegistration()) instanceof ParentResourceRegistration) {
            $parent = $child->getResourceRegistry()->get($registration->parentResource);

            $chain[] = [
                'parameter' => $child->getParentRouteParameterName(),
                'resource' => $parent,
                'property' => $child->getParentRecordProperty(),
            ];

            $child = $parent;
        }

        return $chain;
    }

    /**
     * The property on this resource's record pointing back at its parent record,
     * or null where the association has no single field to read it through.
     */
    private function getParentRecordProperty(): ?string
    {
        $target = $this->getAssociationReader()->describe($this->getParentRecordClass(), $this->getParentRelationshipName());

        if (! $target instanceof AssociationTarget || $target->isManyToMany) {
            return null;
        }

        return $target->mappedBy;
    }

    /**
     * The ancestor route parameters a caller left out, read off the records it
     * did pass: each level's parent is the property the level below points at
     * it through, walked from the nearest known record object upwards. A level
     * the caller passed as a key rather than a record is walked *through* — the
     * anchor stays where it was and the path to the next level grows — so a
     * caller mixing a record with ancestor keys still guesses what it left out.
     * A level nothing can be read from — a many-to-many step, an unset
     * property, no record at all — drops the anchor, leaving the generator to
     * refuse whatever is still missing.
     *
     * @param list<array{parameter: string, resource: Resource, property: string|null}> $chain
     * @param array<string, mixed>                                                      $parameters
     * @param array<string, object>                                                     $records    the records a caller passed, by parameter name
     *
     * @return array<string, mixed>
     */
    private function guessAncestorParameters(array $chain, array $parameters, array $records): array
    {
        $anchorRecord = $records['record'] ?? null;

        /** @var list<string> $anchorPath */
        $anchorPath = [];

        foreach ($chain as $link) {
            $passedRecord = $records[$link['parameter']] ?? null;

            if ($passedRecord !== null) {
                $anchorRecord = $passedRecord;
                $anchorPath = [];

                continue;
            }

            if ($link['property'] === null) {
                $anchorRecord = null;

                continue;
            }

            $anchorPath[] = $link['property'];

            if ($anchorRecord === null || filled($parameters[$link['parameter']] ?? null)) {
                continue;
            }

            $ancestorRecord = $this->getPropertyAccessor()->getFromRecord($anchorRecord, implode('.', $anchorPath));

            if (! is_object($ancestorRecord)) {
                $anchorRecord = null;

                continue;
            }

            $parameters[$link['parameter']] = $link['resource']->getRecordRouteKey($ancestorRecord);
            $anchorRecord = $ancestorRecord;
            $anchorPath = [];
        }

        return $parameters;
    }

    private function getParentResourceInstance(): Resource
    {
        return $this->getResourceRegistry()->get($this->getParentRegistrationOrFail()->parentResource);
    }

    /**
     * @return class-string
     */
    private function getParentRecordClass(): string
    {
        return $this->getParentResourceInstance()->getRecordClass();
    }

    private function getParentRegistrationOrFail(): ParentResourceRegistration
    {
        return $this->getParentResourceRegistration()
            ?? throw new LogicException(sprintf('Resource [%s] names no parent resource, so it has no parent relationship — set the $parentResource static property.', static::class));
    }
}
