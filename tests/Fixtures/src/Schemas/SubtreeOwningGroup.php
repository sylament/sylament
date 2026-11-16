<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Components\Group;

/**
 * A group that owns its whole record subtree, the shape of a relationship repeater or an
 * entangled layout component without the association behind either, and routes the
 * relationship hooks beneath it only at the owner-relative paths `routeHooksAt()` names.
 */
class SubtreeOwningGroup extends Group
{
    /**
     * @var list<string>
     */
    private array $routedPaths = [];

    public function routeHooksAt(string ...$paths): static
    {
        $this->routedPaths = array_values($paths);

        return $this;
    }

    public function ownsRecordSubtree(): bool
    {
        return true;
    }

    public function routesNestedRelationshipHookAt(string $path): bool
    {
        return in_array($path, $this->routedPaths, true);
    }
}
