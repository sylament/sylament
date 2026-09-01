<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources;

/**
 * One parent → child resource link: the two resources, and the association the
 * child records hang off — {@see getRelationshipName()} answers null until a
 * caller names one, which leaves the child free to derive it from the mapping.
 */
final readonly class ParentResourceRegistration
{
    /**
     * @param class-string<Resource> $parentResource
     * @param class-string<Resource> $childResource
     */
    public function __construct(
        public string $parentResource,
        public string $childResource,
        private ?string $relationshipName = null,
    ) {
    }

    public function relationship(string $name): static
    {
        return new self($this->parentResource, $this->childResource, $name);
    }

    public function getRelationshipName(): ?string
    {
        return $this->relationshipName;
    }
}
