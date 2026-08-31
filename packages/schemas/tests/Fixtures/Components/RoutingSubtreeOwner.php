<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

/**
 * A subtree owner that answers the routing vouch, standing in for the forms
 * `Repeater` and the entangled layout components without their walks: it owns
 * its record subtree and routes the paths its allow-list names, so both arms of
 * the delegation edge — vouched and refused — are expressible at map level.
 * `SubtreeOwner` is final, so this extends `StateField` beside it.
 */
final class RoutingSubtreeOwner extends StateField
{
    /** @var list<string>|null */
    private ?array $routedPaths = null;

    /**
     * The owner-relative paths this owner routes; `null` — the default — routes
     * every path beneath it.
     *
     * @param list<string>|null $paths
     */
    public function routedPaths(?array $paths): static
    {
        $this->routedPaths = $paths;

        return $this;
    }

    public function ownsRecordSubtree(): bool
    {
        return true;
    }

    public function routesNestedRelationshipHookAt(string $path): bool
    {
        return ($this->routedPaths === null) || in_array($path, $this->routedPaths, true);
    }
}
