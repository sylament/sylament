<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages\Concerns;

use Closure;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Resources\Resource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The records a nested page hangs under: their route keys on the wire, root
 * first, and the chain they resolve to.
 */
trait InteractsWithParentRecord
{
    /**
     * The ancestor route keys, keyed by the parameter each travels under and
     * ordered root-first — keys, never records, exactly as {@see $record} is.
     *
     * @var array<string, string>
     */
    #[LiveProp]
    public array $parentRecords = [];

    /**
     * @var list<array{parameter: string, resource: Resource, record: object}>|null
     */
    private ?array $resolvedParentRecords = null;

    /**
     * The record directly above this page, or null where the resource nests
     * under nothing.
     */
    public function getParentRecord(): ?object
    {
        $chain = $this->getResolvedParentRecords();

        return $chain === [] ? null : array_last($chain)['record'];
    }

    public function getParentRecordTitle(): string
    {
        $resource = $this->getParentResourceInstance()
            ?? throw new LogicException(sprintf('The page [%s] belongs to a resource that names no parent, so it has no parent record to title.', static::class));

        $record = $this->getParentRecord();

        return $record !== null && $resource->hasRecordTitle()
            ? $resource->getRecordTitle($record)
            : $resource->getTitleCaseRecordLabel();
    }

    /**
     * The modifier a record resolution runs through while a parent is in force:
     * this resource's records narrowed to that parent record, and null where the
     * page hangs under nothing. Every key a page resolves goes through it —
     * including the unchecksummed one a mounted action's descriptor carries.
     */
    public function getParentRecordScope(): ?Closure
    {
        $parentRecord = $this->getParentRecord();

        if ($parentRecord === null) {
            return null;
        }

        $resource = $this->getResourceInstance();

        return fn (): QueryBuilder => $resource->getParentScopedRecordQuery($parentRecord);
    }

    public function getParentResourceInstance(): ?Resource
    {
        $registration = $this->getResourceInstance()->getParentResourceRegistration();

        return $registration instanceof ParentResourceRegistration
            ? $this->getResourceRegistry()->get($registration->parentResource)
            : null;
    }

    /**
     * The ancestors above this page, root-first: each one resolved through its
     * own resource's binding, scoped to the record resolved one level above it,
     * so a key naming a record of another branch is refused at the step it
     * belongs to rather than reached through. Memoized for the request; the
     * keys are re-resolved on the next one.
     *
     * @return list<array{parameter: string, resource: Resource, record: object}>
     */
    protected function getResolvedParentRecords(): array
    {
        if ($this->resolvedParentRecords !== null) {
            return $this->resolvedParentRecords;
        }

        $chain = [];
        $scope = null;

        foreach ($this->getAncestorResourceLinks() as [$ancestorResource, $childResource]) {
            $parameter = $childResource->getParentRouteParameterName();
            $key = $this->parentRecords[$parameter] ?? null;
            $record = $key === null ? null : $ancestorResource->resolveRecordRouteBinding($key, $scope);

            if ($record === null) {
                throw new NotFoundHttpException(sprintf('Parent record [%s] not found for [%s].', $key ?? '?', $ancestorResource::class));
            }

            $chain[] = [
                'parameter' => $parameter,
                'resource' => $ancestorResource,
                'record' => $record,
            ];

            $scope = fn (): QueryBuilder => $childResource->getParentScopedRecordQuery($record);
        }

        return $this->resolvedParentRecords = $chain;
    }

    /**
     * Every parent → child link above this page's resource, root-first.
     *
     * @return list<array{Resource, Resource}>
     */
    private function getAncestorResourceLinks(): array
    {
        $links = [];
        $child = $this->getResourceInstance();

        while (($registration = $child->getParentResourceRegistration()) instanceof ParentResourceRegistration) {
            $parent = $this->getResourceRegistry()->get($registration->parentResource);

            array_unshift($links, [$parent, $child]);

            $child = $parent;
        }

        return $links;
    }
}
