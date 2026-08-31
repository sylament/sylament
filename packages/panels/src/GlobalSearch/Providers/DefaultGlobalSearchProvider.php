<?php

declare(strict_types=1);

namespace Sylament\Panels\GlobalSearch\Providers;

use Sylament\Panels\CurrentPanel;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;

final readonly class DefaultGlobalSearchProvider implements GlobalSearchProvider
{
    public function __construct(
        private CurrentPanel $currentPanel,
        private PanelRegistry $panels,
        private ResourceRegistry $resources,
    ) {
    }

    /**
     * The contract's `null` arm is for a custom provider that answers nothing
     * at all; this one always hands back a builder, empty categories and all.
     */
    public function getResults(string $query): GlobalSearchResults
    {
        $builder = GlobalSearchResults::make();
        $panel = $this->currentPanel->get() ?? $this->panels->getDefault();

        $resources = array_map($this->resources->get(...), $panel->getResources());

        usort(
            $resources,
            fn (Resource $a, Resource $b): int => ($a->getGlobalSearchSort() ?? 0) <=> ($b->getGlobalSearchSort() ?? 0),
        );

        foreach ($resources as $resource) {
            if (! $resource->canGloballySearch()) {
                continue;
            }

            $results = $resource->getGlobalSearchResults($query);

            if ($results === []) {
                continue;
            }

            $builder->category($resource->getPluralRecordLabel(), $results);
        }

        return $builder;
    }
}
