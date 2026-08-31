<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\RelationManagers;

class RelationManagerConfiguration
{
    /**
     * @param class-string<RelationManager> $relationManager
     * @param array<string, mixed> $properties
     */
    public function __construct(
        public readonly string $relationManager,
        protected array $properties = [],
    ) {
    }

    /**
     * The mount props this registration adds to the embed, over the manager's
     * own defaults.
     *
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
